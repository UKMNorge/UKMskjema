<?php

use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall    = new HandleAPICall(['skjema_id', 'sporsmal_id'], [], ['POST'], false);
$skjemaId      = (int) $handleCall->getArgument('skjema_id');
$sporsmalId    = (int) $handleCall->getArgument('sporsmal_id');
$arrangementId = (int) get_option('pl_id');

// Valider tilgang til skjemaet
$skjema = null;
foreach (['getArrangementSkjema', 'getDeltakerSkjema'] as $method) {
    try {
        $s = Skjema::$method($arrangementId);
        if ((int) $s->getId() === $skjemaId) {
            $skjema = $s;
            break;
        }
    } catch (Exception $e) {}
}
if (!$skjema) {
    try {
        foreach (Skjema::getOppgaveSkjemaer($arrangementId) as $s) {
            if ((int) $s->getId() === $skjemaId) {
                $skjema = $s;
                break;
            }
        }
    } catch (Exception $e) {}
}
if (!$skjema) {
    $handleCall->sendErrorToClient('Du har ikke tilgang til dette spørreskjemaet', 403);
}

try {
    Write::fjernSporsmalFraSkjema($sporsmalId, $skjemaId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'     => true,
    'sporsmal_id' => $sporsmalId,
    'skjema_id'   => $skjemaId,
]);
