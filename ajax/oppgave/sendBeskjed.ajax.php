<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Samtykkeskjema\BeskjedSuper;
use UKMNorge\Samtykkeskjema\OppgaveBeskjed;
use UKMNorge\Samtykkeskjema\Write as SamtykkeWrite;

require_once 'UKM/Autoloader.php';
require_once 'UKM/sms.class.php';

$handleCall = new HandleAPICall(['oppgave_id', 'respondenter_ids'], ['rolle'], ['POST'], false);

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

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet og beskjeden kan ikke sendes.', 403);
}

$rolleRaw = $handleCall->getOptionalArgument('rolle');
$rolle = ($rolleRaw === null || $rolleRaw === '') ? BeskjedSuper::ROLLE_DELTAKER : (string) $rolleRaw;
try {
    $rolle = BeskjedSuper::validateRolle($rolle);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), 400);
}

$idsRaw = $handleCall->getArgument('respondenter_ids');
if (is_string($idsRaw)) {
    $json = function_exists('wp_unslash') ? wp_unslash($idsRaw) : stripslashes($idsRaw);
    $respondentIds = json_decode($json, true);
} else {
    $respondentIds = $idsRaw;
}

if (!is_array($respondentIds) || count($respondentIds) === 0) {
    $handleCall->sendErrorToClient('respondenter_ids må være en liste av IDer.', 400);
}

$idLookup = [];
foreach ($respondentIds as $id) {
    $id = (int) $id;
    if ($id > 0) {
        $idLookup[$id] = true;
    }
}

if (count($idLookup) === 0) {
    $handleCall->sendErrorToClient('Ingen gyldige respondent-IDer.', 400);
}

$isVideresending = $oppgave->getType() === Oppgave::TYPE_VIDERESENDING;
$valgte = [];
foreach ($oppgave->getAlleRespondenter($isVideresending, null) as $respondent) {
    if (!($respondent instanceof DeltaRespondent)) {
        continue;
    }
    $id = (int) $respondent->getId();
    if (!isset($idLookup[$id]) || isset($valgte[$id])) {
        continue;
    }
    $valgte[$id] = $respondent;
}

if (count($valgte) === 0) {
    $handleCall->sendErrorToClient('Fant ingen gyldige respondenter å sende til.', 400);
}

$lenke = 'https://delta.ukm.no/ukmid/oppgaveliste/' . $oppgave->getPlId() . '/';
$skipSend = defined('UKM_HOSTNAME') && UKM_HOSTNAME === 'ukm.dev';
$sistePerTelefon = OppgaveBeskjed::getSistePerTelefonForOppgave($oppgave, $rolle);

$sendt = [];
$feilet = [];
$hoppetOver = [];
$nyligSendt = [];

foreach ($valgte as $respondent) {
    $navn = trim($respondent->getNavn() . ' ' . $respondent->getEtternavn());
    if ($navn === '') {
        $navn = 'du';
    }

    if ($rolle === BeskjedSuper::ROLLE_FORESATT) {
        $phone = (string) preg_replace('/\D/', '', (string) $respondent->getForesattMobil());
        $message = 'Hei! Du er oppgitt som foresatt for ' . $navn . '. Du må derfor godkjenne noen samtykker og opplysninger. Klikk på lenken for å godkjenne: ' . $lenke;
    } else {
        $phone = (string) preg_replace('/\D/', '', (string) $respondent->getMobil());
        $message = 'Hei, ' . $navn . '! Du har en oppgave som du må besvare. Klikk på lenken for å besvare oppgaven: ' . $lenke;
    }

    if ($phone === '') {
        $feilet[] = [
            'id'    => (int) $respondent->getId(),
            'navn'  => $navn,
            'error' => 'Mangler mobilnummer',
        ];
        continue;
    }

    $sisteBeskjed = $sistePerTelefon[$phone] ?? null;
    if (isset($nyligSendt[$phone]) || ($sisteBeskjed !== null && $sisteBeskjed->erSendtSisteDogn())) {
        $hoppetOver[] = [
            'id'    => (int) $respondent->getId(),
            'navn'  => $navn,
            'error' => 'SMS er allerede sendt siste 24 timer.',
        ];
        continue;
    }

    try {
        if (!(defined('UKM_HOSTNAME') && UKM_HOSTNAME === 'ukm.dev')) {
            $sms = new SMS('samtykke', get_current_user_id());
            $sms->text($message)
                ->to($phone)
                ->from('UKMNorge')
                ->ok();
            $report = $sms->report();
            if (!is_numeric($report)) {
                throw new Exception(is_string($report) && $report !== '' ? $report : 'SMS kunne ikke sendes.');
            }
        }

        SamtykkeWrite::registrerOppgaveBeskjed($oppgave, $rolle, $message, $phone);
        $nyligSendt[$phone] = true;
        $sendt[] = [
            'id'    => (int) $respondent->getId(),
            'phone' => $phone,
        ];
    } catch (Exception $e) {
        $feilet[] = [
            'id'    => (int) $respondent->getId(),
            'navn'  => $navn,
            'error' => $e->getMessage(),
        ];
    }
}

$handleCall->sendToClient([
    'success'     => count($sendt) > 0,
    'sendt'       => count($sendt),
    'feilet'      => count($feilet),
    'hoppet_over' => count($hoppetOver),
    'sendt_til'   => $sendt,
    'feil'        => $feilet,
    'hoppet'      => $hoppetOver,
]);
