<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall(['type'], [], ['POST'], false);

$type = $handleCall->getArgument('type');
if (!in_array($type, ['arrangement', 'person'], true)) {
    $handleCall->sendErrorToClient('Ugyldig type. Må være "arrangement" eller "person".', 400);
}

$arrangementId = get_option('pl_id');
if (!$arrangementId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

// Sjekk at det ikke allerede eksisterer et skjema av denne typen
try {
    $existing = $type === 'arrangement'
        ? Skjema::getArrangementSkjema((int) $arrangementId)
        : Skjema::getDeltakerSkjema((int) $arrangementId);
    $handleCall->sendErrorToClient('Det eksisterer allerede et ' . $type . '-skjema for dette arrangementet.', 409);
} catch (Exception $e) {
    // Skjema finnes ikke — fortsett med oppretting
}

try {
    $arrangement = new Arrangement((int) $arrangementId);
    $skjema = $type === 'arrangement'
        ? Write::createForArrangement($arrangement)
        : Write::createForPerson($arrangement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'        => true,
    'id'             => (int) $skjema->getId(),
    'arrangement_id' => (int) $skjema->getArrangementId(),
    'type'           => $skjema->getType(),
    'sporsmal'       => [],
]);
