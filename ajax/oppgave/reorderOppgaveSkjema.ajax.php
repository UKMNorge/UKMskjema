<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'rad_ids'], [], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$radIdsRaw = $handleCall->getArgument('rad_ids');

if (is_string($radIdsRaw)) {
    $radIds = json_decode($radIdsRaw, true);
} else {
    $radIds = $radIdsRaw;
}

if (!is_array($radIds)) {
    $handleCall->sendErrorToClient('rad_ids må være en liste av tall.', 400);
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
    OppgaveWrite::rekjorKjedeEtterRadIds($oppgaveId, $radIds);
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
