<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveRespondentVisning;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Samtykke\Person as PersonSamtykke;
use UKMNorge\Arrangement\Arrangement;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(
    ['oppgave_id', 'phone', 'skjema_type', 'skjema_id', 'sporsmal_id'],
    [],
    ['GET', 'POST'],
    false
);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$arrangement = new Arrangement($plId);
if ($arrangement === null) {
    $handleCall->sendErrorToClient('Fant ikke arrangementet', 404);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$phone = trim((string) $handleCall->getArgument('phone'));
$skjemaType = trim((string) $handleCall->getArgument('skjema_type'));
$skjemaId = (int) $handleCall->getArgument('skjema_id');
$sporsmalId = (int) $handleCall->getArgument('sporsmal_id');

if ($oppgaveId < 1) {
    $handleCall->sendErrorToClient('Ugyldig oppgave_id', 400);
}
if ($phone === '') {
    $handleCall->sendErrorToClient('Ugyldig phone', 400);
}
if ($skjemaType === '') {
    $handleCall->sendErrorToClient('Ugyldig skjema_type', 400);
}
if ($skjemaId < 1 || $sporsmalId < 1) {
    $handleCall->sendErrorToClient('Ugyldig skjema_id eller sporsmal_id', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet', 403);
}

$respondent = DeltaRespondent::loadByMobil($phone);
if ($respondent === null) {
    $handleCall->sendErrorToClient('Fant ikke respondent med dette mobilnummeret', 403);
}

try {
    $status = OppgaveRespondentVisning::getStatusForImportBildeFilmSamtykkeTilPersonvern(
        $oppgave,
        $respondent,
        $skjemaType,
        $skjemaId,
        $sporsmalId
    );
} catch (Exception $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
    $handleCall->sendErrorToClient($e->getMessage(), $code);
}

$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP'])
    ? $_SERVER['HTTP_CF_CONNECTING_IP']
    : $_SERVER['REMOTE_ADDR'];

foreach ($arrangement->getInnslag()->getAll() as $innslag) {
    foreach ($innslag->getPersoner()->getAll() as $person) {
        if ($person->getMobil() != $respondent->getMobil()) {
            continue;
        }
        $samtykke = new PersonSamtykke($person, $innslag);
        $samtykke->setStatus($status['user_status'], $ip);
        if ($status['foresatt_status'] !== null && $samtykke->harForesatt()) {
            $samtykke->setForesattStatus($status['foresatt_status'], $ip);
        }
        $samtykke->persist();
    }
}

$handleCall->sendToClient([
    'success'           => true,
    'user_status'       => $status['user_status'],
    'foresatt_status'   => $status['foresatt_status'],
    'foresatt_godkjent' => $status['foresatt_godkjent'],
    'message'           => 'Film- og fotosamtykke er importert til brukeren i hele systemet',
]);
