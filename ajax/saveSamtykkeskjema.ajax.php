<?php

use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\SamtykkeProsjekt;
use UKMNorge\Samtykkeskjema\SamtykkeVersjon;

$skjemaId = isset($_POST['skjema_id']) ? (int) $_POST['skjema_id'] : null;

if (!$skjemaId) {
    throw new Exception('skjema_id er påkrevd.');
}

$skjema = new SamtykkeSkjema($skjemaId);

// Oppdater navn
$navn = isset($_POST['navn']) ? trim($_POST['navn']) : null;
if (!empty($navn)) {
    $skjema->setNavn($navn);
    $skjema->save();
}

// Prosjekter
$savedProsjekter = [];
$prosjekterRaw = isset($_POST['prosjekter']) ? $_POST['prosjekter'] : null;
if ($prosjekterRaw) {
    $prosjekter = is_array($prosjekterRaw) ? $prosjekterRaw : json_decode($prosjekterRaw, true);

    if (is_array($prosjekter)) {
        foreach ($prosjekter as $p) {
            if (!empty($p['id'])) {
                $prosjekt = new SamtykkeProsjekt((int) $p['id']);
                if (!empty($p['navn'])) {
                    $prosjekt->setNavn($p['navn']);
                }
                if (array_key_exists('beskrivelse', $p)) {
                    $prosjekt->setBeskrivelse($p['beskrivelse']);
                }
                if (array_key_exists('arrangement_id', $p)) {
                    $prosjekt->setArrangementId($p['arrangement_id'] ? (int) $p['arrangement_id'] : null);
                }
                $prosjekt->save();
            } else {
                $prosjekt = SamtykkeProsjekt::create(
                    $skjemaId,
                    $p['navn'] ?? '',
                    $p['beskrivelse'] ?? null,
                    isset($p['arrangement_id']) && $p['arrangement_id'] ? (int) $p['arrangement_id'] : null
                );
            }

            $savedProsjekter[] = [
                'id'             => $prosjekt->getId(),
                'skjema_id'      => $skjemaId,
                'navn'           => $prosjekt->getNavn(),
                'beskrivelse'    => $prosjekt->getBeskrivelse(),
                'arrangement_id' => $prosjekt->getArrangementId(),
            ];
        }
    }
}

// Versjon
$savedVersjon = null;
$versjonRaw = isset($_POST['versjon']) ? $_POST['versjon'] : null;
if ($versjonRaw) {
    $v = is_array($versjonRaw) ? $versjonRaw : json_decode($versjonRaw, true);

    if (is_array($v)) {
        if (!empty($v['id'])) {
            $versjon = new SamtykkeVersjon((int) $v['id']);
            if (!empty($v['versjon_nr'])) {
                $versjon->setVersjonNr($v['versjon_nr']);
            }
            if (array_key_exists('beskrivelse', $v)) {
                $versjon->setBeskrivelse($v['beskrivelse']);
            }
            if (array_key_exists('body_text', $v)) {
                $versjon->setBodyText($v['body_text']);
            }
            if (array_key_exists('file_path', $v)) {
                $versjon->setFilePath($v['file_path']);
            }
            $versjon->save();
        } else {
            $versjon = SamtykkeVersjon::create(
                $skjemaId,
                $v['versjon_nr'] ?? '1.0',
                $v['beskrivelse'] ?? null,
                $v['body_text'] ?? null,
                $v['file_path'] ?? null
            );
        }

        $savedVersjon = [
            'id'          => $versjon->getId(),
            'skjema_id'   => $skjemaId,
            'versjon_nr'  => $versjon->getVersjonNr(),
            'beskrivelse' => $versjon->getBeskrivelse(),
            'body_text'   => $versjon->getBodyText(),
            'file_path'   => $versjon->getFilePath(),
        ];
    }
}

UKMskjema::addResponseData('success', true);
UKMskjema::addResponseData('skjema', [
    'id'         => $skjema->getId(),
    'navn'       => $skjema->getNavn(),
    'prosjekter' => $savedProsjekter,
    'versjon'    => $savedVersjon,
]);
