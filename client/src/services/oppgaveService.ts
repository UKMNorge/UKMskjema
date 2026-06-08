import OppgaveRespondent, {
    type OppgaveRespondentData,
    type OppgaveSvarStatus,
} from '@/objects/OppgaveRespondent';

export interface OppgaveSkjemaKjedeItem {
    id: number;
    oppgave_id: number;
    skjema_type: string;
    skjema_id: number;
    neste_type: string | null;
    neste_id: number | null;
}

export interface OppgaveData {
    id: number;
    name: string;
    type: string | null;
    pl_id: number;
    description: string | null;
    locked: boolean;
    skjema_kjede: OppgaveSkjemaKjedeItem[];
}

export interface SkjemaValgItem {
    id: number;
    navn: string;
}

export interface OppgaveOversiktResponse {
    success: boolean;
    oppgaver: OppgaveData[];
    skjema_valg: Record<string, SkjemaValgItem[]>;
    arrangement_type: string;
}

function getSpaInteraction(): any {
    return (window as any).spaInteraction;
}

export async function hentOppgaveOversikt(): Promise<OppgaveOversiktResponse> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getOppgaveOversikt',
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke hente oppgaver');
    }

    return res as OppgaveOversiktResponse;
}

function normalizeRespondenterListe(respondenter: unknown): OppgaveRespondent[] {
    if (!respondenter) {
        return [];
    }
    const rader = Array.isArray(respondenter)
        ? respondenter
        : Object.values(respondenter as Record<string, unknown>);

    return rader
        .filter((r) => r && typeof r === 'object')
        .map((r) => OppgaveRespondent.fromAjax(r as Record<string, unknown>))
        .filter((r) => r.id > 0);
}

export async function hentAlleRespondenter(oppgaveId: number): Promise<OppgaveRespondent[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getAlleRespondenter',
        oppgave_id: oppgaveId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke hente respondenter');
    }

    const liste = normalizeRespondenterListe(res.respondenter);
    return liste.sort((a, b) => a.getNavnFullt().localeCompare(b.getNavnFullt(), 'nb'));
}

export interface OppgaveSvarLinje {
    label: string;
    value: string;
}

export interface OppgaveSkjemaKjedeVisning {
    ledd_id: number;
    skjema_id: number;
    skjema_type: string;
    skjema_type_label: string;
    skjema_navn: string;
    besvart: boolean;
    foresatt_godkjent: boolean;
    venter_foresatt: boolean;
    indicator: 'success' | 'warning' | 'danger';
    detalj: OppgaveSkjemaDetalj;
}

export interface OppgaveSamtykkeSvar {
    svar: string;
    kommentar: string | null;
    created_at: string;
    skjema_type: string;
}

export type SamtykkeSkjemaType = 'vanlig' | 'med-kommentar' | 'janei';
export type SamtykkeSkjemaSubtype = 'standard' | 'bilde_film';

export interface OppgaveSkjemaDetalj {
    type: 'samtykkeskjema' | 'sporreskjema' | 'ukjent';
    /** Samtykkeskjema: vanlig | med-kommentar | janei (kun type med-kommentar har kommentarfelt) */
    samtykke_type?: SamtykkeSkjemaType;
    /** Samtykkeskjema: standard | bilde_film */
    samtykke_subtype?: SamtykkeSkjemaSubtype | null;
    versjoner?: { beskrivelse: string; body_text: string }[];
    svar?: OppgaveSamtykkeSvar | null;
    sporsmal?: {
        id: number;
        type: string;
        tittel: string;
        hjelp: string;
        linjer: OppgaveSvarLinje[];
        foresatt_godkjent: boolean | null;
    }[];
}

export interface RespondentOppgavelisteResponse {
    success: boolean;
    oppgave: {
        id: number;
        name: string;
        description: string | null;
    };
    respondent: {
        id: number;
        delta_user_id: number;
        navn: string;
        etternavn: string;
        mobil: string;
        foresatt_navn: string | null;
        foresatt_mobil: string | null;
        navn_fullt: string;
        is_18: boolean;
    };
    person_id: number;
    kjede: OppgaveSkjemaKjedeVisning[];
}

export async function hentRespondentOppgaveliste(
    oppgaveId: number,
    phone: string
): Promise<RespondentOppgavelisteResponse> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getRespondentOppgaveliste',
        oppgave_id: oppgaveId,
        phone: phone.trim(),
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke hente oppgaveliste');
    }

    return res as RespondentOppgavelisteResponse;
}

export interface OppgaveSporsmalValg {
    skjema_type: string;
    skjema_id: number;
    skjema_navn: string;
    /** Samtykkeskjema: standard | bilde_film */
    skjema_subtype?: SamtykkeSkjemaSubtype | null;
    sporsmal_id: number;
    tittel: string;
    type: string;
    label: string;
}

export interface RespondentSporsmalSvar {
    sporsmal_id: number;
    tittel: string;
    linjer: OppgaveSvarLinje[];
    foresatt_godkjent: boolean | null;
}

export async function hentOppgaveSporsmalListe(oppgaveId: number): Promise<OppgaveSporsmalValg[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getOppgaveSporsmalListe',
        oppgave_id: oppgaveId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke hente spørsmål');
    }

    return (res.sporsmal ?? []) as OppgaveSporsmalValg[];
}

export async function hentRespondentSporsmalSvar(
    oppgaveId: number,
    phone: string,
    skjemaType: string,
    skjemaId: number,
    sporsmalId: number
): Promise<RespondentSporsmalSvar> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getRespondentSporsmalSvar',
        oppgave_id: oppgaveId,
        phone: phone.trim(),
        skjema_type: skjemaType,
        skjema_id: skjemaId,
        sporsmal_id: sporsmalId,
    });

    if (!res.success) {
        // throw new Error(res.message ?? res.result ?? 'Kunne ikke hente svar');
    }

    return res as RespondentSporsmalSvar;
}

export async function importRespondentIntoleranser(
    oppgaveId: number,
    phone: string,
    skjemaId: number,
    sporsmalId: number
): Promise<void> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/importRespondentIntoleranser',
        oppgave_id: oppgaveId,
        phone: phone.trim(),
        skjema_id: skjemaId,
        sporsmal_id: sporsmalId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke importere intoleranser');
    }
}

export async function importRespondentBildeFilmSamtykke(
    oppgaveId: number,
    phone: string,
    skjemaType: string,
    skjemaId: number,
    sporsmalId: number
): Promise<void> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/importRespondentBildeFilmSamtykke',
        oppgave_id: oppgaveId,
        phone: phone.trim(),
        skjema_type: skjemaType,
        skjema_id: skjemaId,
        sporsmal_id: sporsmalId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke importere film- og fotosamtykke');
    }
}

export async function hentRespondentSvarStatus(
    oppgaveId: number,
    phone: string
): Promise<OppgaveSvarStatus> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/getRespondentSvarStatus',
        oppgave_id: oppgaveId,
        phone: phone.trim(),
    }, {
        onError: (error : any) => {
            throw new Error(error.message ?? 'Kunne ikke hente svarstatus');
        },
    });

    return Number(res.svar_status) as OppgaveSvarStatus;
}

export async function opprettOppgave(
    name: string,
    type: string | null,
    description: string | null
): Promise<void> {
    const payload: Record<string, unknown> = {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/createOppgave',
        name,
    };
    if (type) {
        payload.type = type;
    }
    if (description) {
        payload.description = description;
    }

    const res = await getSpaInteraction().runAjaxCall('/', 'POST', payload);

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke opprette oppgave');
    }
}

export async function slettOppgave(oppgaveId: number): Promise<void> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/deleteOppgave',
        oppgave_id: oppgaveId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke slette oppgave');
    }
}

export async function leggTilSkjemaIKjede(
    oppgaveId: number,
    skjemaType: string,
    skjemaId: number
): Promise<OppgaveSkjemaKjedeItem[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/appendOppgaveSkjema',
        oppgave_id: oppgaveId,
        skjema_type: skjemaType,
        skjema_id: skjemaId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke legge til skjema');
    }

    return res.skjema_kjede as OppgaveSkjemaKjedeItem[];
}

export async function fjernSkjemaFraKjede(oppgaveSkjemaRadId: number): Promise<OppgaveSkjemaKjedeItem[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/removeOppgaveSkjema',
        oppgave_skjema_id: oppgaveSkjemaRadId,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke fjerne skjema');
    }

    return res.skjema_kjede as OppgaveSkjemaKjedeItem[];
}

/**
 * Ny rekkefølge som liste av `oppgave_skjema.id` (alle ledd må være med).
 */
export async function reorderOppgaveKjede(
    oppgaveId: number,
    radIds: number[]
): Promise<OppgaveSkjemaKjedeItem[]> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/reorderOppgaveSkjema',
        oppgave_id: oppgaveId,
        rad_ids: JSON.stringify(radIds),
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke endre rekkefølge');
    }

    return res.skjema_kjede as OppgaveSkjemaKjedeItem[];
}

export async function toggleOppgaveLock(oppgaveId: number, locked: boolean): Promise<boolean> {
    const res = await getSpaInteraction().runAjaxCall('/', 'POST', {
        action: 'UKMskjema_ajax',
        controller: 'oppgave/toggleLock',
        oppgave_id: oppgaveId,
        locked: locked ? 1 : 0,
    });

    if (!res.success) {
        throw new Error(res.message ?? res.result ?? 'Kunne ikke oppdatere lås');
    }

    return !!res.locked;
}
