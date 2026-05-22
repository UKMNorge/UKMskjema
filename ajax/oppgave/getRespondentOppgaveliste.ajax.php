<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'respondent_id'], [], ['GET', 'POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$respondentId = (int) $handleCall->getArgument('respondent_id');

if ($oppgaveId < 1) {
    $handleCall->sendErrorToClient('Ugyldig oppgave_id', 400);
}
if ($respondentId < 1) {
    $handleCall->sendErrorToClient('Ugyldig respondent_id', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet', 403);
}

$respondent = DeltaRespondent::loadById($respondentId);
if ($respondent === null) {
    $handleCall->sendErrorToClient('Fant ikke respondenten', 404);
}

$data = $oppgave->getRespondentOppgaveliste($respondent);

$handleCall->sendToClient(array_merge(['success' => true], $data));
