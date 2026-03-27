<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;

$navn = isset($_POST['navn']) ? trim($_POST['navn']) : null;

if (empty($navn)) {
    throw new Exception('Navn er påkrevd for å opprette samtykkeskjema.');
}

$skjema = SamtykkeSkjema::create($navn);

UKMskjema::addResponseData('success', true);
UKMskjema::addResponseData('skjema', [
    'id'   => $skjema->getId(),
    'navn' => $skjema->getNavn(),
]);
