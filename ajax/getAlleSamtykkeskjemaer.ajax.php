<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\OAuth2\HandleAPICall;


$handleCall = new HandleAPICall([], [], ['GET', 'POST'], false);

$arrangementId = get_option('pl_id');
if (!$arrangementId) {
    $handleCall->sendErrorToClient('arrangementId er påkrevd.', 400);
}

$skjemaer = SamtykkeSkjema::getAllByArrangementId($arrangementId);

$result = [];
foreach ($skjemaer as $skjema) {
    $result[] = $skjema->getJson();
}

$handleCall->sendToClient([
    'success'  => true,
    'skjemaer' => $result,
]);
