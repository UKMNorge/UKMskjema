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
    /** Foresatts mobilnummer (Delta). */
    foresatt_mobil?: string | null;
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
    foresatt_mobil: string | null;
    svar_status: OppgaveSvarStatus | null;

    constructor(data?: Partial<OppgaveRespondentData>) {
        this.id = data?.id ?? 0;
        this.navn = data?.navn ?? '';
        this.etternavn = data?.etternavn ?? '';
        this.mobil = data?.mobil ?? '';
        this.videresending_nominasjon = !!data?.videresending_nominasjon;
        this.fylke = data?.fylke ?? null;
        this.arrangement = data?.arrangement ?? null;
        this.foresatt_mobil = data?.foresatt_mobil ?? null;
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
            foresatt_mobil:
                data.foresatt_mobil != null && data.foresatt_mobil !== '' ? String(data.foresatt_mobil) : null,
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
