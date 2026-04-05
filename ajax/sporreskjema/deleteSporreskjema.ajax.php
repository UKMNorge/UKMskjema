<?php

use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall    = new HandleAPICall(['skjema_id'], [], ['POST'], false);
$skjemaId      = (int) $handleCall->getArgument('skjema_id');
$arrangementId = (int) get_option('pl_id');

// Valider tilgang: arrangement- og deltakerskjema (én hver), pluss evt. flere oppgave-skjemaer
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

// Slett alle spørsmål som ikke har svar, og fjern skjemaet
try {
    foreach ($skjema->getSporsmal()->getAll() as $sporsmal) {
        try {
            Write::fjernSporsmalFraSkjema($sporsmal->getId(), $skjemaId);
        } catch (Exception $e) {
            // Spørsmål med svar kan ikke slettes — fortsett
        }
    }

    $delete = new Delete('ukm_videresending_skjema', ['id' => $skjemaId]);
    $delete->run();
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success' => true,
    'id'      => $skjemaId,
]);
