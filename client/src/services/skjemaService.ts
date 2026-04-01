import type { SamtykkeSkjemaData, SamtykkeProsjektData, SamtykkeVersjonData } from '../objects/SamtykkeSkjema';


function getAjaxUrl(): string {
    const url = (window as any).ajaxurl as string | undefined;
    if (!url) {
        throw new Error('ajaxurl er ikke definert');
    }
    return url;
}

/**
 * Henter alle samtykkeskjemaer med prosjekter og siste versjon.
 */
export async function hentAlleSamtykkeskjemaer(): Promise<SamtykkeSkjemaData[]> {
    var spaInteraction = (<any>window).spaInteraction;

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/getAlleSamtykkeskjemaer',
    };
    
    // Yes, you have access to `spaInteraction` here. It is defined at the top of this file (line 3)
    var res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke hente samtykkeskjemaer');
    }

    return res.skjemaer as SamtykkeSkjemaData[];
}

/**
 * Oppretter et nytt samtykkeskjema med gitt navn.
 * Returnerer det opprettede skjemaet { id, navn }.
 */
export async function opprettSamtykkeskjema(navn: string): Promise<{ id: number; navn: string }> {
    var spaInteraction = (<any>window).spaInteraction;

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/createSamtykkeskjema',
        navn: navn,
    };

    var res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke opprette samtykkeskjema');
    }

    return res as { id: number; navn: string };
}

/**
 * Lagrer all data for et eksisterende samtykkeskjema:
 *  - Oppdaterer navn
 *  - Oppretter eller oppdaterer prosjekter
 *  - Oppretter eller oppdaterer versjon
 */
export async function lagreAllDataSamtykkeskjema(
    skjemaId: number,
    navn: string,
    prosjekter?: SamtykkeProsjektData[],
    versjon?: SamtykkeVersjonData | null
): Promise<any> {
    const payload: Record<string, unknown> = { skjema_id: skjemaId, navn };
    var spaInteraction = (<any>window).spaInteraction;

    if (prosjekter && prosjekter.length > 0) {
        payload.prosjekter = prosjekter;
    }

    if (versjon) {
        lagreVersjonSamtykkeskjema(skjemaId, versjon);
    }

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/saveSamtykkeskjema',
        skjema_id: skjemaId,
        navn: navn,
    };

    const res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke lagre samtykkeskjema');
    }

    return res as { id: number; navn: string };
}

/**
 * Sletter et samtykkeskjema permanent, inkludert versjoner, prosjekter og entiteter.
 */
export async function slettSamtykkeskjema(skjemaId: number): Promise<void> {
    var spaInteraction = (<any>window).spaInteraction;

    var data: any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/deleteSamtykkeskjema',
        skjema_id: skjemaId,
    };

    var res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke slette samtykkeskjema');
    }
}

async function lagreVersjonSamtykkeskjema(
    skjemaId: number,
    versjon: SamtykkeVersjonData
): Promise<any> {
    const payload: SamtykkeVersjonData = versjon;
    var spaInteraction = (<any>window).spaInteraction;

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/saveOrCreateSamtykkeskjemaVersion',
        skjema_id: skjemaId,
        versjon_id: versjon.id ?? -1,
        beskrivelse: versjon.beskrivelse,
        body_text: versjon.body_text,
        file_path: versjon.file_path,
    };

    var res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke lagre versjon av samtykkeskjema');
    }

    return res as { id: number; versjon_nr: string };
}
