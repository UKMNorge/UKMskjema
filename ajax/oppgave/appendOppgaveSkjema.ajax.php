<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveSkjema;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'skjema_type', 'skjema_id'], [], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$skjemaType = (string) $handleCall->getArgument('skjema_type');
$skjemaId = (int) $handleCall->getArgument('skjema_id');

$gyldigeTyper = [OppgaveSkjema::SKJEMA_SAMTYKKE, OppgaveSkjema::SKJEMA_VIDERESENDING];
if (!in_array($skjemaType, $gyldigeTyper, true)) {
    $handleCall->sendErrorToClient('Ugyldig skjema_type.', 400);
}

if ($skjemaId < 1) {
    $handleCall->sendErrorToClient('skjema_id er ugyldig.', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven.', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet.', 403);
}

try {
    $ny = OppgaveWrite::appendSkjemaTilKjede($oppgaveId, $skjemaType, $skjemaId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$oppgaveOppdatert = new Oppgave($oppgaveId);
$kjede = [];
foreach ($oppgaveOppdatert->getSkjemaKjede() as $ledd) {
    $kjede[] = [
        'id'          => $ledd->getId(),
        'oppgave_id'  => $ledd->getOppgaveId(),
        'skjema_type' => $ledd->getSkjemaType(),
        'skjema_id'   => $ledd->getSkjemaId(),
        'neste_type'  => $ledd->getNesteType(),
        'neste_id'    => $ledd->getNesteId(),
    ];
}

$handleCall->sendToClient([
    'success'      => true,
    'skjema_kjede' => $kjede,
]);
