<?php

use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\OAuth2\HandleAPICall;

$handleCall = new HandleAPICall(['navn'], ['type', 'subtype'], ['POST'], false);

$navn = $handleCall->getArgument('navn');
$type = $handleCall->getOptionalArgument('type') ?: 'vanlig';
$subtype = $handleCall->getOptionalArgument('subtype');

$arrangement = null;
$arrangementId = get_option('pl_id');
if ($arrangementId) {
    $arrangement = new Arrangement($arrangementId);
}

$skjema = null;
try {
    $skjema = Write::create($navn, $arrangement, $type, $subtype);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'id'      => (int)$skjema->getId(),
    'navn'    => $skjema->getNavn(),
    'type'    => $skjema->getType(),
    'subtype' => $skjema->getSubtype(),
    'success' => true,
]);
