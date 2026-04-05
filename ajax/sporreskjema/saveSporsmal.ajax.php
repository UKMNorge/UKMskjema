<?php

use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall(
    ['skjema_id', 'type', 'tittel'],
    ['sporsmal_id', 'rekkefolge', 'tekst'],
    ['POST'],
    false
);

$skjemaId      = (int) $handleCall->getArgument('skjema_id');
$sporsmalId    = (int) ($handleCall->getOptionalArgument('sporsmal_id') ?? 0);
$type          = $handleCall->getArgument('type');
$tittel        = $handleCall->getArgument('tittel');
$tekst         = $handleCall->getOptionalArgument('tekst') ?? '';
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
    if ($sporsmalId === 0) {
        $rekkefolge = (int) ($handleCall->getOptionalArgument('rekkefolge') ?? ($skjema->getSporsmal()->getAntall() + 1));
        $sporsmal   = Write::createSporsmal($skjema, $rekkefolge, $type, $tittel, $tekst);
    } else {
        // Finn eksisterende spørsmål
        $sporsmal = null;
        foreach ($skjema->getSporsmal()->getAll() as $s) {
            if ((int) $s->getId() === $sporsmalId) {
                $sporsmal = $s;
                break;
            }
        }
        if (!$sporsmal) {
            $handleCall->sendErrorToClient('Finner ikke spørsmål med ID ' . $sporsmalId, 404);
        }
        $sporsmal->setType($type);
        $sporsmal->setTittel($tittel);
        $sporsmal->setTekst($tekst);
        if ($handleCall->getOptionalArgument('rekkefolge') !== null) {
            // Rekkefolge can not be set via a setter in the current model;
            // pass it through on create only. Reordering is handled by saveSporreskjema.
        }
        Write::saveSporsmal($sporsmal);
    }
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'    => true,
    'id'         => (int) $sporsmal->getId(),
    'skjema_id'  => (int) $sporsmal->getSkjemaId(),
    'rekkefolge' => (int) $sporsmal->getRekkefolge(),
    'type'       => $sporsmal->getType(),
    'tittel'     => $sporsmal->getTittel(),
    'tekst'      => $sporsmal->getTekst(),
]);
