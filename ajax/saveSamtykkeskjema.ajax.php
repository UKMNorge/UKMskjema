<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

$arrangementId = get_option('pl_id');
$skjemaer = SamtykkeSkjema::getAllByArrangementId($arrangementId);

$handleCall = new HandleAPICall(['skjema_id', 'navn'], [], ['POST'], false);

$skjemaId = (int) $handleCall->getArgument('skjema_id');
$navn = $handleCall->getArgument('navn');

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
$skjema = Write::save($skjema);

$handleCall->sendToClient([
    'id'   => (int)$skjema->getId(),
    'navn' => $skjema->getNavn(),
    'success' => true,
]);