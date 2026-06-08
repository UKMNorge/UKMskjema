<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'phone'], [], ['GET', 'POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$phone = trim((string) $handleCall->getArgument('phone'));

if ($oppgaveId < 1) {
    $handleCall->sendErrorToClient('Ugyldig oppgave_id', 400);
}
if ($phone === '') {
    $handleCall->sendErrorToClient('Ugyldig phone', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $found = false;
    $isVideresending = $oppgave->getType() === Oppgave::TYPE_VIDERESENDING;
    
    foreach ($oppgave->getAlleRespondenter($isVideresending ? true : false, $plId) as $respondent) {
        if (!($respondent instanceof DeltaRespondent)) {
            continue;
        }
        if ($respondent->getMobil() == $phone) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $handleCall->sendErrorToClient('Fant ikke respondent med dette mobilnummeret du har tilgang til', 403);
    }
}

$respondent = DeltaRespondent::loadByMobil($phone);
if ($respondent === null) {
    $handleCall->sendErrorToClient('Fant ikke respondent med dette mobilnummeret', 403);
}

$data = $oppgave->getRespondentOppgaveliste($respondent);

$handleCall->sendToClient(array_merge(['success' => true], $data));
