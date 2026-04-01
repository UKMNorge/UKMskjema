export interface SamtykkeProsjektData {
    id?: number;
    skjema_id?: number;
    navn: string;
    beskrivelse?: string | null;
    arrangement_id?: number | null;
}

export interface SamtykkeVersjonData {
    id?: number;
    skjema_id?: number;
    versjon_nr: string;
    beskrivelse?: string | null;
    body_text?: string | null;
    file_path?: string | null;
}

export interface SamtykkeSkjemaData {
    id: number;
    navn: string;
    prosjekter?: SamtykkeProsjektData[];
    versjon?: SamtykkeVersjonData | null;
}

export class SamtykkeSkjema {
    id: number;
    navn: string;
    prosjekter: SamtykkeProsjektData[];
    versjon: SamtykkeVersjonData | null;

    // UI state (not serialised)
    expanded: boolean = false;
    activeTab: string = 'generelt';
    visNyProsjektForm: boolean = false;
    nyProsjekt: { navn: string; beskrivelse: string | null; arrangement_id: number | null } = {
        navn: '', beskrivelse: null, arrangement_id: null,
    };

    constructor(data?: Partial<SamtykkeSkjemaData>) {
        this.id         = data?.id ?? 0;
        this.navn       = data?.navn ?? '';
        this.prosjekter = data?.prosjekter ?? [];
        this.versjon    = data?.versjon ?? null;
    }

    toJSON(): SamtykkeSkjemaData {
        return {
            id:         this.id,
            navn:       this.navn,
            prosjekter: this.prosjekter,
            versjon:    this.versjon,
        };
    }
}
