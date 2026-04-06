<?php

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

$oppgaverUt = [];
try {
    foreach (Oppgave::getAllByArrangement($plId) as $oppgave) {
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
        $oppgaverUt[] = [
            'id'          => $oppgave->getId(),
            'name'        => $oppgave->getName(),
            'type'        => $oppgave->getType(),
            'pl_id'       => $oppgave->getPlId(),
            'description' => $oppgave->getDescription(),
            'skjema_kjede'=> $kjede,
        ];
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
    'success' => true,
    'oppgaver' => $oppgaverUt,
    'skjema_valg' => [
        OppgaveSkjema::SKJEMA_SAMTYKKE        => $samtykkeValg,
        OppgaveSkjema::SKJEMA_VIDERESENDING => $videresendingValg,
    ],
]);
