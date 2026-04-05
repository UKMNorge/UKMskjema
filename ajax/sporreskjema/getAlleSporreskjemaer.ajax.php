<?php

use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall([], [], ['GET', 'POST'], false);

$arrangementId = get_option('pl_id');
if (!$arrangementId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$result = [];

try {
    $skjemaer = Skjema::getOppgaveSkjemaer((int) $arrangementId);
    foreach ($skjemaer as $skjema) {
        $sporsmal = [];
        foreach ($skjema->getSporsmal()->getAll() as $s) {
            $sporsmal[] = [
                'id'         => (int) $s->getId(),
                'skjema_id'  => (int) $s->getSkjemaId(),
                'rekkefolge' => (int) $s->getRekkefolge(),
                'type'       => $s->getType(),
                'tittel'     => $s->getTittel(),
                'tekst'      => $s->getTekst(),
            ];
        }
        $result[] = [
            'id'             => (int) $skjema->getId(),
            'navn'           => $skjema->getNavn(),
            'arrangement_id' => (int) $skjema->getArrangementId(),
            'type'           => $skjema->getType(),
            'sporsmal'       => $sporsmal,
        ];
    }
} catch (Exception $e) {
    // Skjema finnes ikke for denne typen — det er OK
}


$handleCall->sendToClient([
    'success'    => true,
    'skjemaer'   => $result,
]);
