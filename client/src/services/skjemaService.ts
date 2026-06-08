import type { SamtykkeSkjemaData, SamtykkeProsjektData, SamtykkeVersjonData } from '../objects/SamtykkeSkjema';


function getAjaxUrl(): string {
    const url = (window as any).ajaxurl as string | undefined;
    if (!url) {
        throw new Error('ajaxurl er ikke definert');
    }
    return url;
}

export interface SamtykkeskjemaListeResponse {
    skjemaer: SamtykkeSkjemaData[];
    arrangement_type: string;
}

/**
 * Henter alle samtykkeskjemaer med prosjekter og siste versjon.
 */
export async function hentAlleSamtykkeskjemaer(): Promise<SamtykkeskjemaListeResponse> {
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

    return {
        skjemaer: res.skjemaer as SamtykkeSkjemaData[],
        arrangement_type: res.arrangement_type as string,
    };
}

/**
 * Oppretter et nytt samtykkeskjema med gitt navn.
 * Returnerer det opprettede skjemaet { id, navn }.
 */
export async function opprettSamtykkeskjema(
    navn: string,
    type?: string,
    subtype?: string | null
): Promise<{ id: number; navn: string; type?: string; subtype?: string | null }> {
    var spaInteraction = (<any>window).spaInteraction;

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/createSamtykkeskjema',
        navn: navn,
    };
    
    if (type) {
        data.type = type;
    }
    if (subtype !== undefined && subtype !== null) {
        data.subtype = subtype;
    }

    var res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke opprette samtykkeskjema');
    }

    return res as { id: number; navn: string; type?: string; subtype?: string | null };
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
    type?: string,
    subtype?: string | null,
    prosjekter?: SamtykkeProsjektData[],
    versjon?: SamtykkeVersjonData | null
): Promise<any> {
    var spaInteraction = (<any>window).spaInteraction;

    var data : any = {
        action: 'UKMskjema_ajax',
        controller: 'samtykkeskjema/saveSamtykkeskjema',
        skjema_id: skjemaId,
        navn: navn,
    };
    
    if (type) {
        data.type = type;
    }
    if (subtype !== undefined) {
        data.subtype = subtype ?? '';
    }

    const res = await spaInteraction.runAjaxCall('/', 'POST', data);

    if (!res.success) {
        throw new Error(res.message ?? 'Kunne ikke lagre samtykkeskjema');
    }

    // Lagre versjon (hvis sendt inn) før vi henter fersk skjema-data.
    if (versjon) {
        await lagreVersjonSamtykkeskjema(skjemaId, versjon);
    }

    // Endpointet `saveSamtykkeskjema` returnerer ikke versjon/prosjekter.
    // Returner alltid fersk skjema-json fra `getAlleSamtykkeskjemaer` slik at UI får med `versjon`.
    return await hentSamtykkeskjemaById(skjemaId);
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

    return res;
}

async function hentSamtykkeskjemaById(skjemaId: number): Promise<SamtykkeSkjemaData> {
    const { skjemaer } = await hentAlleSamtykkeskjemaer();
    const funnet = skjemaer.find(s => (s as any).id === skjemaId);
    if (!funnet) {
        throw new Error('Kunne ikke finne oppdatert samtykkeskjema etter lagring');
    }
    return funnet;
}
