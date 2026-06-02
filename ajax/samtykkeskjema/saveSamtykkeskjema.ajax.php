<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

$arrangementId = get_option('pl_id');
$skjemaer = SamtykkeSkjema::getAllByArrangementId($arrangementId);

$handleCall = new HandleAPICall(['skjema_id', 'navn'], ['type', 'subtype'], ['POST'], false);

$skjemaId = (int) $handleCall->getArgument('skjema_id');
$navn = $handleCall->getArgument('navn');
$type = $handleCall->getOptionalArgument('type');
$subtype = $handleCall->getOptionalArgument('subtype');

$skjema = null;
foreach($skjemaer as $s) {
    if($s->getId() == $skjemaId) {
        $skjema = $s;
        break;
    }
}

if(!$skjema) {
    $handleCall->sendErrorToClient('Du har ikke tilgang til samtykkeskjemaet med ID ' . $skjemaId, 403);
}

$skjema->setNavn($navn);
if ($type !== null) {
    $skjema->setType($type);
}
if ($subtype !== null) {
    $skjema->setSubtype($subtype !== '' ? $subtype : null);
}
$skjema = Write::save($skjema);

$handleCall->sendToClient([
    'id'      => (int)$skjema->getId(),
    'navn'    => $skjema->getNavn(),
    'type'    => $skjema->getType(),
    'subtype' => $skjema->getSubtype(),
    'success' => true,
]);