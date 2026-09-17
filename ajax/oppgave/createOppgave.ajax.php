<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['name'], ['type', 'description', 'consent_age_requirement'], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$name = trim((string) $handleCall->getArgument('name'));
if ($name === '') {
    $handleCall->sendErrorToClient('Navn er påkrevd.', 400);
}

$typeRaw = $handleCall->getOptionalArgument('type');
$type = ($typeRaw === null || $typeRaw === '') ? null : (string) $typeRaw;
if ($type !== null && $type !== Oppgave::TYPE_VIDERESENDING && $type !== Oppgave::TYPE_REISELEDERE && $type !== Oppgave::TYPE_FYLKESKONTAKTER && $type !== Oppgave::TYPE_DELTAKERE) {
    $handleCall->sendErrorToClient('Ugyldig oppgavetype.', 400);
}

$descRaw = $handleCall->getOptionalArgument('description');
$description = ($descRaw === null || $descRaw === '') ? null : (string) $descRaw;

$consentAgeRaw = $handleCall->getOptionalArgument('consent_age_requirement');
$consentAgeRequirement = ($consentAgeRaw === null || $consentAgeRaw === '') ? null : (string) $consentAgeRaw;
if (!Oppgave::isValidConsentAgeRequirement($consentAgeRequirement)) {
    $handleCall->sendErrorToClient('Ugyldig alderskrav for samtykke.', 400);
}

try {
    $oppgave = OppgaveWrite::createOppgave($name, $plId, $type, $description, $consentAgeRequirement);
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'                   => true,
    'id'                        => $oppgave->getId(),
    'name'                      => $oppgave->getName(),
    'type'                      => $oppgave->getType(),
    'pl_id'                     => $oppgave->getPlId(),
    'description'               => $oppgave->getDescription(),
    'consent_age_requirement'   => $oppgave->getConsentAgeRequirement(),
    'skjema_kjede'              => [],
]);
