<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveSkjema;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_skjema_id'], [], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$radId = (int) $handleCall->getArgument('oppgave_skjema_id');

try {
    $rad = new OppgaveSkjema($radId);
    $oppgave = new Oppgave($rad->getOppgaveId());
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgave-skjema.', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet.', 403);
}

try {
    OppgaveWrite::removeOppgaveSkjemaFraKjede($radId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$oppgaveOppdatert = new Oppgave($oppgave->getId());
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
