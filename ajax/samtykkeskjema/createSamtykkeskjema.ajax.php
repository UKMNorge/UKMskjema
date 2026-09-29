<?php

use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\OAuth2\HandleAPICall;

$handleCall = new HandleAPICall(['navn'], ['type', 'subtype', 'parent_consent_requirement'], ['POST'], false);

$navn = $handleCall->getArgument('navn');
$type = $handleCall->getOptionalArgument('type') ?: 'vanlig';
$subtype = $handleCall->getOptionalArgument('subtype');
$parentConsentRequirement = $handleCall->getOptionalArgument('parent_consent_requirement');

$arrangement = null;
$arrangementId = get_option('pl_id');
if ($arrangementId) {
    $arrangement = new Arrangement($arrangementId);
}

$skjema = null;
try {
    $skjema = Write::create($navn, $arrangement, $type, $subtype, $parentConsentRequirement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'id'      => (int)$skjema->getId(),
    'navn'    => $skjema->getNavn(),
    'type'    => $skjema->getType(),
    'subtype' => $skjema->getSubtype(),
    'parent_consent_requirement' => $skjema->getParentConsentRequirement(),
    'success' => true,
]);
