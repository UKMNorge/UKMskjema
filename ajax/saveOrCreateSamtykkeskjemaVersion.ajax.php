<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\SamtykkeVersjon;
use UKMNorge\Samtykkeskjema\Write;
use UKMNorge\OAuth2\HandleAPICall;


$handleCall = new HandleAPICall(['skjema_id', 'versjon_id'], ['beskrivelse', 'body_text', 'file_path'], ['POST'], false);

$skjemaId = (int) $handleCall->getArgument('skjema_id');
$versjonId = (int) $handleCall->getArgument('versjon_id');

// Sjekk om skjemaet eksisterer i dette arrangementet
$arrangementId = get_option('pl_id');
$skjemaer = SamtykkeSkjema::getAllByArrangementId($arrangementId);

$skjema = null;
foreach($skjemaer as $s) {
    if($s->getId() == $skjemaId) {
        $skjema = $s;
        break;
    }
}

if(!$skjema) {
    $handleCall->sendErrorToClient('Du har ikke tilgang til dette samtykkeskjemaet', 403);
}

// Optional arguments
$beskrivelse = $handleCall->getOptionalArgument('beskrivelse') ?? '';
$bodyText = $handleCall->getOptionalArgument('body_text') ?? '';
$filePath = $handleCall->getOptionalArgument('file_path') ?? null;

// Opprett skjema siden det ikke eksisterer
if($versjonId == -1) {
    $versjon = SamtykkeVersjon::create($skjemaId, '1', $beskrivelse, $bodyText, $filePath);
}

// Versjon
$versjon = $skjema->getVersjon($versjonId);

if(!$versjon) {
    $handleCall->sendErrorToClient('Fant ikke versjon med ID i dette samtykkeskjemaet ' . $versjonId, 404);
}

$versjon->setBeskrivelse($beskrivelse);
$versjon->setBodyText($bodyText);
$versjon->setFilePath($filePath);

$versjon = Write::saveVersjon($skjema, $versjon);

$handleCall->sendToClient([
    'skjema'      => $skjema->getJson(),
    'success'     => true,
]);
