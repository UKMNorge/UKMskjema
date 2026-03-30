<?php

use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\OAuth2\HandleAPICall;

$handleCall = new HandleAPICall(['navn'], [], ['POST'], false);

$navn = $handleCall->getArgument('navn');

$arrangement = null;
$arrangementId = get_option('pl_id');
if ($arrangementId) {
    $arrangement = new Arrangement($arrangementId);
}

$skjema = null;
try {
    $skjema = Write::create($navn, $arrangement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'id'   => (int)$skjema->getId(),
    'navn' => $skjema->getNavn(),
    'success' => true,
]);
