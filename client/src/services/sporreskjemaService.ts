import type { SporreSkjemaData, SporsmalData } from '../objects/SporreSkjema';

function getSpaInteraction(): any {
    return (window as any).spaInteraction;
}

/**
 * Henter alle spørreskjemaer (arrangement + person) for dette arrangementet.
 */
export async function hentAlleSporreskjemaer(): Promise<SporreSkjemaData[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:     'UKMskjema_ajax',
        controller: 'sporreskjema/getAlleSporreskjemaer',
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke hente spørreskjemaer');
    }

    return res.skjemaer as SporreSkjemaData[];
}

/**
 * Oppretter et nytt spørreskjema av gitt type (arrangement|person).
 * Returnerer det opprettede skjemaet.
 */
export async function opprettSporreskjema(
    type: 'arrangement' | 'person'
): Promise<SporreSkjemaData> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:     'UKMskjema_ajax',
        controller: 'sporreskjema/createSporreskjema',
        type,
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke opprette spørreskjema');
    }

    return res as SporreSkjemaData;
}

/**
 * Lagrer spørsmål-listen for et eksisterende spørreskjema.
 * Nye spørsmål (id == 0) opprettes, eksisterende oppdateres.
 * Returnerer det oppdaterte skjemaet med lagrede spørsmål.
 */
export async function lagreSporreskjema(
    skjemaId: number,
    sporsmal: SporsmalData[]
): Promise<SporreSkjemaData> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:     'UKMskjema_ajax',
        controller: 'sporreskjema/saveSporreskjema',
        skjema_id:  skjemaId,
        sporsmal:   JSON.stringify(sporsmal),
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke lagre spørreskjema');
    }

    return res as SporreSkjemaData;
}

/**
 * Sletter et spørreskjema og alle spørsmål uten svar.
 */
export async function slettSporreskjema(skjemaId: number): Promise<void> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:     'UKMskjema_ajax',
        controller: 'sporreskjema/deleteSporreskjema',
        skjema_id:  skjemaId,
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke slette spørreskjema');
    }
}

/**
 * Oppretter eller oppdaterer ett enkelt spørsmål.
 * Send sporsmal_id == 0 (eller utelat) for å opprette nytt.
 */
export async function lagreSporsmal(
    skjemaId: number,
    sporsmal: Partial<SporsmalData>
): Promise<SporsmalData> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:      'UKMskjema_ajax',
        controller:  'sporreskjema/saveSporsmal',
        skjema_id:   skjemaId,
        sporsmal_id: sporsmal.id ?? 0,
        type:        sporsmal.type,
        tittel:      sporsmal.tittel,
        tekst:       sporsmal.tekst ?? '',
        rekkefolge:  sporsmal.rekkefolge ?? 1,
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke lagre spørsmål');
    }

    return res as SporsmalData;
}

/**
 * Sletter ett enkelt spørsmål. Feiler hvis spørsmålet allerede har svar.
 */
export async function slettSporsmal(
    skjemaId: number,
    sporsmalId: number
): Promise<void> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action:      'UKMskjema_ajax',
        controller:  'sporreskjema/deleteSporsmal',
        skjema_id:   skjemaId,
        sporsmal_id: sporsmalId,
    });

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke slette spørsmål');
    }
}
