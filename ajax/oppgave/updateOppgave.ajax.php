<?php

use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\Write as OppgaveWrite;
use UKMNorge\OAuth2\HandleAPICall;

require_once 'UKM/Autoloader.php';

$handleCall = new HandleAPICall(['oppgave_id', 'name'], ['description'], ['POST'], false);

$plId = (int) get_option('pl_id');
if (!$plId) {
    $handleCall->sendErrorToClient('pl_id er ikke satt for dette arrangementet.', 400);
}

$oppgaveId = (int) $handleCall->getArgument('oppgave_id');

try {
    $oppgave = new Oppgave($oppgaveId);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Fant ikke oppgaven.', 404);
}

if ($oppgave->getPlId() !== $plId) {
    $handleCall->sendErrorToClient('Oppgaven tilhører ikke dette arrangementet.', 403);
}

if ($oppgave->isLocked()) {
    $handleCall->sendErrorToClient('Oppgaven er publisert og kan ikke endres.', 400);
}

$name = trim((string) $handleCall->getArgument('name'));
if ($name === '') {
    $handleCall->sendErrorToClient('Navn er påkrevd.', 400);
}

$descRaw = $handleCall->getOptionalArgument('description');
$description = ($descRaw === null || trim((string) $descRaw) === '') ? null : trim((string) $descRaw);

try {
    $oppgave = OppgaveWrite::updateOppgave(
        $oppgaveId,
        $name,
        $oppgave->getPlId(),
        $oppgave->getType(),
        $description
    );
} catch (Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), $e->getCode() ?: 500);
}

$handleCall->sendToClient([
    'success'     => true,
    'id'          => $oppgave->getId(),
    'name'        => $oppgave->getName(),
    'description' => $oppgave->getDescription(),
]);
