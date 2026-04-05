<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall([], [], ['POST'], false);

$arrangementId = get_option('pl_id');
if (!$arrangementId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

try {
    $arrangement = new Arrangement((int) $arrangementId);
    $skjema = Write::createForOppgave($arrangement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'        => true,
    'id'             => (int) $skjema->getId(),
    'navn'           => $skjema->getNavn(),
    'arrangement_id' => (int) $skjema->getArrangementId(),
    'type'           => $skjema->getType(),
    'sporsmal'       => [],
]);
