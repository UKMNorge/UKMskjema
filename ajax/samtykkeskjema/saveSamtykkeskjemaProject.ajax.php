// Prosjekter
$savedProsjekter = [];
$prosjekterRaw = $handleCall->getOptionalArgument('prosjekter');
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