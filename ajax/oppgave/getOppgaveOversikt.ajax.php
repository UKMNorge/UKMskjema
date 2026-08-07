<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveSkjema;
use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Samtykkeskjema\SamtykkeSkjema;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall([], [], ['GET', 'POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

try {
    $arrangement = new Arrangement($plId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Kunne ikke hente arrangementet', 401);
}

$erLandArrangement = $arrangement->getType() == 'land';

$oppgaveArr = array_merge(
    Oppgave::getAllByArrangement($plId),
    Oppgave::getAlleByRespondentArrangement($plId)
);
// Fjern duplikater basert på oppgave-ID
$alleOppgaver = [];
$unikeOppgaveId = [];
foreach($oppgaveArr as $oppgave) {
    $id = $oppgave->getId();
    if (!isset($unikeOppgaveId[$id])) {
        $alleOppgaver[] = $oppgave;
        $unikeOppgaveId[$id] = true;
    }
}

$oppgaverUt = [];
$arrangementNavnCache = [];
try {
    foreach ($alleOppgaver as $oppgave) {
        $kjede = [];
        foreach ($oppgave->getSkjemaKjede() as $ledd) {
            $kjede[] = [
                'id'          => $ledd->getId(),
                'oppgave_id'  => $ledd->getOppgaveId(),
                'skjema_type' => $ledd->getSkjemaType(),
                'skjema_id'   => $ledd->getSkjemaId(),
                'neste_type'  => $ledd->getNesteType(),
                'neste_id'    => $ledd->getNesteId(),
            ];
        }
        $oppgavePlId = $oppgave->getPlId();
        $rad = [
            'id'          => $oppgave->getId(),
            'name'        => $oppgave->getName(),
            'type'        => $oppgave->getType(),
            'pl_id'       => $oppgavePlId,
            'description' => $oppgave->getDescription(),
            'locked'      => $oppgave->isLocked(),
            'skjema_kjede'=> $kjede,
        ];
        if ($oppgavePlId !== $plId) {
            if (!isset($arrangementNavnCache[$oppgavePlId])) {
                $arrangementNavnCache[$oppgavePlId] = $oppgave->getArrangement()->getNavn();
            }
            $rad['arrangement_navn'] = $arrangementNavnCache[$oppgavePlId];
        }
        $oppgaverUt[] = $rad;
    }
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$samtykkeValg = [];
try {
    foreach (SamtykkeSkjema::getAllByArrangementId($plId) as $s) {
        $samtykkeValg[] = [
            'id'   => (int) $s->getId(),
            'navn' => $s->getNavn(),
        ];
    }
} catch (Exception $e) {
    // ingen samtykkeskjema er OK
}

$videresendingValg = [];
try {
    foreach (Skjema::getOppgaveSkjemaer($plId) as $sk) {
        $videresendingValg[] = [
            'id'   => (int) $sk->getId(),
            'navn' => $sk->getNavn() !== '' ? $sk->getNavn() : ('Skjema #' . $sk->getId()),
        ];
    }
} catch (Exception $e) {
    // ingen spørreskjema er OK
}

$handleCall->sendToClient([
    'success'          => true,
    'oppgaver'         => $oppgaverUt,
    'skjema_valg'      => [
        OppgaveSkjema::SKJEMA_SAMTYKKE      => $samtykkeValg,
        OppgaveSkjema::SKJEMA_VIDERESENDING => $videresendingValg,
    ],
    'arrangement_type' => $arrangement->getType(),
]);
