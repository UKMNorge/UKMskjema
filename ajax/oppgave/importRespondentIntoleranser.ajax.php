<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveRespondentVisning;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'phone', 'skjema_id', 'sporsmal_id'], [], ['GET', 'POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$phone = trim((string) $handleCall->getArgument('phone'));
$skjemaId = (int) $handleCall->getArgument('skjema_id');
$sporsmalId = (int) $handleCall->getArgument('sporsmal_id');

if ($oppgaveId < 1) {
    $handleCall->sendErrorToClient('Ugyldig oppgave_id', 400);
}
if ($phone === '') {
    $handleCall->sendErrorToClient('Ugyldig phone', 400);
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
    OppgaveRespondentVisning::importIntoleranserTilSensitivt($oppgave, $respondent, $skjemaId, $sporsmalId);
} catch (Exception $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
    $handleCall->sendErrorToClient($e->getMessage(), $code);
}

$handleCall->sendToClient([
    'success' => true,
    'message' => 'Intoleranser er importert til brukeren i hele systemet',
]);
