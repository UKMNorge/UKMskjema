<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

$handleCall = new HandleAPICall(['skjema_id'], [], ['POST'], false);

$skjemaId = (int) $handleCall->getArgument('skjema_id');

try {
    $skjema = new SamtykkeSkjema($skjemaId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Samtykkeskjema med ID ' . $skjemaId . ' finnes ikke', 404);
}

try {
    Write::delete($skjema);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success' => true,
    'id'      => $skjemaId,
]);
