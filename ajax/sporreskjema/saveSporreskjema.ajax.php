<?php

use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Sporsmal;
use UKMNorge\Arrangement\Skjema\Write;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall(['skjema_id'], ['sporsmal'], ['POST'], false);

$skjemaId     = (int) $handleCall->getArgument('skjema_id');
$sporsmalRaw  = $handleCall->getOptionalArgument('sporsmal');
$arrangementId = (int) get_option('pl_id');

// Finn skjemaet og valider at det tilhører dette arrangementet
$skjema = _hentSkjemaForArrangement($skjemaId, $arrangementId, $handleCall);

// Hent eksisterende spørsmål indeksert på ID for oppslag
$eksisterende = [];
foreach ($skjema->getSporsmal()->getAll() as $s) {
    $eksisterende[$s->getId()] = $s;
}

$savedSporsmal = [];

if ($sporsmalRaw) {
    $sporsmalListe = is_array($sporsmalRaw) ? $sporsmalRaw : json_decode($sporsmalRaw, true);

    if (is_array($sporsmalListe)) {
        foreach ($sporsmalListe as $p) {
            $id         = isset($p['id']) ? (int) $p['id'] : 0;
            $rekkefolge = isset($p['rekkefolge']) ? (int) $p['rekkefolge'] : 1;
            $type       = $p['type'] ?? 'tekst';
            $tittel     = $p['tittel'] ?? '';
            $tekst      = $p['tekst'] ?? '';

            try {
                if ($id === 0) {
                    // Nytt spørsmål
                    $sporsmal = Write::createSporsmal($skjema, $rekkefolge, $type, $tittel, $tekst);
                } else {
                    // Eksisterende spørsmål
                    if (!isset($eksisterende[$id])) {
                        continue;
                    }
                    $sporsmal = $eksisterende[$id];
                    $sporsmal->setType($type);
                    $sporsmal->setTittel($tittel);
                    $sporsmal->setTekst($tekst);
                    Write::saveSporsmal($sporsmal);
                }
                $savedSporsmal[] = [
                    'id'         => (int) $sporsmal->getId(),
                    'skjema_id'  => (int) $sporsmal->getSkjemaId(),
                    'rekkefolge' => (int) $sporsmal->getRekkefolge(),
                    'type'       => $sporsmal->getType(),
                    'tittel'     => $sporsmal->getTittel(),
                    'tekst'      => $sporsmal->getTekst(),
                ];
            } catch (Exception $e) {
                $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
            }
        }
    }
}

$handleCall->sendToClient([
    'success'        => true,
    'id'             => (int) $skjema->getId(),
    'arrangement_id' => (int) $skjema->getArrangementId(),
    'type'           => $skjema->getType(),
    'sporsmal'       => $savedSporsmal,
]);

/**
 * Finn og valider at skjemaet tilhører dette arrangementet.
 */
function _hentSkjemaForArrangement(int $skjemaId, int $arrangementId, HandleAPICall $handleCall): Skjema
{
    foreach (['getArrangementSkjema', 'getDeltakerSkjema'] as $method) {
        try {
            $s = Skjema::$method($arrangementId);
            if ((int) $s->getId() === $skjemaId) {
                return $s;
            }
        } catch (Exception $e) {}
    }
    $handleCall->sendErrorToClient('Du har ikke tilgang til dette spørreskjemaet', 403);
}
