/** Status for oppgave-skjema-kjede. Se Oppgave::getOppgaveBesvartStatus. */
export type OppgaveSvarStatus = -1 | 0 | 1 | 2 | 3;

export const OPPGAVE_SVAR_STATUS_UKJENT = -1 as const;
export const OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT = 0 as const;
export const OPPGAVE_SVAR_STATUS_PABEGYNT = 1 as const;
export const OPPGAVE_SVAR_STATUS_VENTER_FORESATT = 2 as const;
export const OPPGAVE_SVAR_STATUS_FULLFORT = 3 as const;

export interface OppgaveRespondentSporsmalSvar {
    linjer: { label: string; value: string }[];
    foresatt_godkjent: boolean | null;
}

export interface OppgaveSisteBeskjed {
    id: number;
    melding: string;
    rolle: 'deltaker' | 'foresatt' | string;
    phone: string;
    created_at: string | null;
    created_at_ts: number;
    sendt_siste_dogn?: boolean;
}

/** Delta-bruker som respondent på en oppgave (fra getAlleRespondenter). */
export interface OppgaveRespondentData {
    id: number;
    navn: string;
    etternavn: string;
    mobil: string;
    /** Fra videresending_nominasjon (kun videresending-oppgaver). */
    videresending_nominasjon: boolean;
    /** Fylke for avsender-arrangement (videresending). */
    fylke?: string | null;
    /** Navn på avsender-arrangement (videresending). */
    arrangement?: string | null;
    /** Foresatts navn (Delta). */
    foresatt_navn?: string | null;
    /** Foresatts mobilnummer (Delta). */
    foresatt_mobil?: string | null;
    /** Siste SMS sendt til deltaker eller foresatt for denne oppgaven. */
    siste_beskjed?: OppgaveSisteBeskjed | null;
    siste_beskjed_deltaker?: OppgaveSisteBeskjed | null;
    siste_beskjed_foresatt?: OppgaveSisteBeskjed | null;
    /** null mens status hentes per respondent (getRespondentSvarStatus). */
    svar_status?: OppgaveSvarStatus | null;
    /** undefined = ikke hentet, null = laster, objekt = hentet svar for valgt spørsmål. */
    sporsmal_svar?: OppgaveRespondentSporsmalSvar | null;
}

export default class OppgaveRespondent {
    id: number;
    navn: string;
    etternavn: string;
    mobil: string;
    videresending_nominasjon: boolean;
    fylke: string | null;
    arrangement: string | null;
    foresatt_navn: string | null;
    foresatt_mobil: string | null;
    siste_beskjed: OppgaveSisteBeskjed | null;
    siste_beskjed_deltaker: OppgaveSisteBeskjed | null;
    siste_beskjed_foresatt: OppgaveSisteBeskjed | null;
    svar_status: OppgaveSvarStatus | null;

    constructor(data?: Partial<OppgaveRespondentData>) {
        this.id = data?.id ?? 0;
        this.navn = data?.navn ?? '';
        this.etternavn = data?.etternavn ?? '';
        this.mobil = data?.mobil ?? '';
        this.videresending_nominasjon = !!data?.videresending_nominasjon;
        this.fylke = data?.fylke ?? null;
        this.arrangement = data?.arrangement ?? null;
        this.foresatt_navn = data?.foresatt_navn ?? null;
        this.foresatt_mobil = data?.foresatt_mobil ?? null;
        this.siste_beskjed_deltaker = parseSisteBeskjed(data?.siste_beskjed_deltaker);
        this.siste_beskjed_foresatt = parseSisteBeskjed(data?.siste_beskjed_foresatt);
        this.siste_beskjed = parseSisteBeskjed(data?.siste_beskjed)
            ?? velgNyesteBeskjed(this.siste_beskjed_deltaker, this.siste_beskjed_foresatt);
        this.svar_status =
            data?.svar_status !== undefined && data?.svar_status !== null
                ? (data.svar_status as OppgaveSvarStatus)
                : null;
    }

    static fromAjax(row: Partial<OppgaveRespondentData> | Record<string, unknown>): OppgaveRespondent {
        const data = row as Record<string, unknown>;
        const harStatus = data.svar_status !== undefined && data.svar_status !== null;
        return new OppgaveRespondent({
            id: Number(data.id) || 0,
            navn: String(data.navn ?? ''),
            etternavn: String(data.etternavn ?? ''),
            mobil: String(data.mobil ?? ''),
            videresending_nominasjon: Boolean(data.videresending_nominasjon),
            fylke: data.fylke != null && data.fylke !== '' ? String(data.fylke) : null,
            arrangement: data.arrangement != null && data.arrangement !== '' ? String(data.arrangement) : null,
            foresatt_navn:
                data.foresatt_navn != null && data.foresatt_navn !== '' ? String(data.foresatt_navn) : null,
            foresatt_mobil:
                data.foresatt_mobil != null && data.foresatt_mobil !== '' ? String(data.foresatt_mobil) : null,
            siste_beskjed: parseSisteBeskjed(data.siste_beskjed),
            siste_beskjed_deltaker: parseSisteBeskjed(data.siste_beskjed_deltaker),
            siste_beskjed_foresatt: parseSisteBeskjed(data.siste_beskjed_foresatt),
            svar_status: harStatus ? (Number(data.svar_status) as OppgaveSvarStatus) : null,
        });
    }

    getNavnFullt(): string {
        return `${this.navn} ${this.etternavn}`.trim();
    }

    getSvarStatusLabel(): string {
        if (this.svar_status === null) {
            return '';
        }
        return oppgaveSvarStatusLabel(this.svar_status);
    }

    getSvarStatusColor(): string {
        if (this.svar_status === null) {
            return 'grey';
        }
        return oppgaveSvarStatusColor(this.svar_status);
    }
}

export function velgNyesteBeskjed(
    a: OppgaveSisteBeskjed | null,
    b: OppgaveSisteBeskjed | null
): OppgaveSisteBeskjed | null {
    if (!a) {
        return b;
    }
    if (!b) {
        return a;
    }
    if (b.created_at_ts > a.created_at_ts || (b.created_at_ts === a.created_at_ts && b.id > a.id)) {
        return b;
    }
    return a;
}

export function parseSisteBeskjed(raw: unknown): OppgaveSisteBeskjed | null {
    if (!raw || typeof raw !== 'object') {
        return null;
    }
    const data = raw as Record<string, unknown>;
    const melding = String(data.melding ?? data.message ?? '').trim();
    const createdAtTs = Number(data.created_at_ts);
    const createdAt = data.created_at != null && data.created_at !== '' ? String(data.created_at) : null;
    if (!melding && !createdAt && !(createdAtTs > 0)) {
        return null;
    }
    return {
        id: Number(data.id) || 0,
        melding,
        rolle: String(data.rolle ?? ''),
        phone: String(data.phone ?? ''),
        created_at: createdAt,
        created_at_ts: createdAtTs > 0 ? createdAtTs : 0,
        sendt_siste_dogn: data.sendt_siste_dogn === true || data.sendt_siste_dogn === 1 || data.sendt_siste_dogn === '1',
    };
}

export function formatSisteBeskjedTid(beskjed: OppgaveSisteBeskjed): string {
    const d = beskjed.created_at_ts > 0
        ? new Date(beskjed.created_at_ts * 1000)
        : (beskjed.created_at ? new Date(beskjed.created_at.replace(' ', 'T')) : null);
    if (!d || Number.isNaN(d.getTime())) {
        return '';
    }
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function sisteBeskjedTekst(beskjed: OppgaveSisteBeskjed): string {
    const tid = formatSisteBeskjedTid(beskjed);
    const mottaker = beskjed.rolle === 'foresatt' ? ' til foresatt' : ' til respondent';
    return tid ? `Sist sendt${mottaker} ${tid}` : `Sist sendt${mottaker}`.trim();
}

export const SMS_VENTETID_SEKUNDER = 24 * 60 * 60;

export function erSendtSisteDogn(beskjed: OppgaveSisteBeskjed | null | undefined): boolean {
    if (!beskjed) {
        return false;
    }
    if (beskjed.created_at_ts > 0) {
        return beskjed.created_at_ts > Date.now() / 1000 - SMS_VENTETID_SEKUNDER;
    }
    return !!beskjed.sendt_siste_dogn;
}

/** True hvis det ikke er sendt SMS til denne rollen siste 24 timer. */
export function kanSendeSms(beskjed: OppgaveSisteBeskjed | null | undefined, rolle: string = 'deltaker'): boolean {
    if (!beskjed) {
        return true;
    }
    if (beskjed.rolle && beskjed.rolle !== rolle) {
        return true;
    }
    return !erSendtSisteDogn(beskjed);
}

export function oppgaveSvarStatusLabel(status: OppgaveSvarStatus): string {
    switch (status) {
        case OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT:
            return 'Ikke påbegynt';
        case OPPGAVE_SVAR_STATUS_PABEGYNT:
            return 'Påbegynt';
        case OPPGAVE_SVAR_STATUS_VENTER_FORESATT:
            return 'Venter på foresatt';
        case OPPGAVE_SVAR_STATUS_FULLFORT:
            return 'Fullført';
        default:
            return 'Ukjent';
    }
}

export function erOppgaveFulfort(status: OppgaveSvarStatus): boolean {
    return status === OPPGAVE_SVAR_STATUS_FULLFORT;
}

export function erVenterForesatt(status: OppgaveSvarStatus): boolean {
    return status === OPPGAVE_SVAR_STATUS_VENTER_FORESATT;
}

export function oppgaveSvarStatusColor(status: OppgaveSvarStatus): string {
    switch (status) {
        case OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT:
        case OPPGAVE_SVAR_STATUS_UKJENT:
            return 'error';
        case OPPGAVE_SVAR_STATUS_PABEGYNT:
            return 'warning';
        case OPPGAVE_SVAR_STATUS_VENTER_FORESATT:
            return 'info';
        case OPPGAVE_SVAR_STATUS_FULLFORT:
            return 'success';
        default:
            return 'grey';
    }
}
