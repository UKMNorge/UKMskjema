<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id'], ['consent_age_requirement'], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');
$consentAgeRaw = $handleCall->getOptionalArgument('consent_age_requirement');
$consentAgeRequirement = ($consentAgeRaw === null || $consentAgeRaw === '') ? null : (string) $consentAgeRaw;
if (!Oppgave::isValidConsentAgeRequirement($consentAgeRequirement)) {
    $handleCall->sendErrorToClient('Ugyldig alderskrav for samtykke.', 400);
}

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven.', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet.', 403);
}

try {
    $oppgave = OppgaveWrite::setConsentAgeRequirement($oppgaveId, $consentAgeRequirement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'                 => true,
    'consent_age_requirement' => $oppgave->getConsentAgeRequirement(),
]);
