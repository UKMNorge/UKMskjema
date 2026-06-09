<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id'], [], ['GET', 'POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
if ($oppgaveId < 1) {
    $handleCall->sendErrorToClient('Ugyldig oppgave_id', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven', 404);
}

$oppgaveFromAnotherArrangement = null;
if ($oppgave->getPlId() !== $plId) {
    foreach(Oppgave::getAlleByRespondentArrangement($plId) as $oppgave) {
        if ($oppgave->getId() === $oppgaveId) {
            $oppgaveFromAnotherArrangement = $oppgave;
            break;
        }
    }
}

$respondenterUt = [];
$isVideresending = $oppgave->getType() === Oppgave::TYPE_VIDERESENDING;
foreach ($oppgave->getAlleRespondenter($isVideresending ? true : false, $oppgaveFromAnotherArrangement != null ? $plId : null) as $respondent) {
    if (!($respondent instanceof DeltaRespondent)) {
        continue;
    }
    $respondenterUt[] = [
        'id'                       => (int) $respondent->getId(),
        'navn'                     => $respondent->getNavn(),
        'etternavn'                => $respondent->getEtternavn(),
        'mobil'                    => $respondent->getMobil(),
        'videresending_nominasjon' => (bool) ($respondent->videresending_nominasjon ?? false),
        'fylke'                    => $respondent->fylke ?? null,
        'arrangement'              => $respondent->arrangement ?? null,
        'foresatt_navn'            => $respondent->getForesattNavn(),
        'foresatt_mobil'           => $respondent->getForesattMobil(),
    ];
}

usort($respondenterUt, static function (array $a, array $b): int {
    $navnA = mb_strtolower($a['etternavn'] . ' ' . $a['navn']);
    $navnB = mb_strtolower($b['etternavn'] . ' ' . $b['navn']);
    return $navnA <=> $navnB;
});

$handleCall->sendToClient([
    'success'      => true,
    'respondenter' => $respondenterUt,
]);
