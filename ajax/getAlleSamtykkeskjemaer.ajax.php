<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;

$skjemaer = SamtykkeSkjema::getAll();

$result = [];
foreach ($skjemaer as $skjema) {
    $lastVersjon = $skjema->getLastVersion();

    $prosjekter = array_map(function ($p) {
        return [
            'id'             => $p->getId(),
            'navn'           => $p->getNavn(),
            'beskrivelse'    => $p->getBeskrivelse(),
            'arrangement_id' => $p->getArrangementId(),
        ];
    }, $skjema->getProsjekter());

    $result[] = [
        'id'         => $skjema->getId(),
        'navn'       => $skjema->getNavn(),
        'prosjekter' => $prosjekter,
        'versjon'    => $lastVersjon ? [
            'id'          => $lastVersjon->getId(),
            'versjon_nr'  => $lastVersjon->getVersjonNr(),
            'beskrivelse' => $lastVersjon->getBeskrivelse(),
            'body_text'   => $lastVersjon->getBodyText(),
            'file_path'   => $lastVersjon->getFilePath(),
        ] : null,
    ];
}

UKMskjema::addResponseData('success', true);
UKMskjema::addResponseData('skjemaer', $result);
