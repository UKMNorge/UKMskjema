export type ParentConsentRequirement = 'u15' | 'u18';

export interface SporsmalData {
    id: number;
    skjema_id: number;
    rekkefolge: number;
    type: string;
    tittel: string;
    tekst: string;
    is_required: boolean;
    parent_consent_requirement: ParentConsentRequirement | null;
}

export interface GruppeData {
    overskrift: SporsmalData | null;
    sporsmal: SporsmalData[];
}

export interface SporreSkjemaData {
    id: number;
    arrangement_id: number;
    /** Alltid oppgave-spørreskjema i denne flyten */
    type: 'oppgave';
    navn?: string;
    parent_consent_requirement?: ParentConsentRequirement | null;
    sporsmal?: SporsmalData[];
}

export class SporreSkjema {
    id: number;
    arrangementId: number;
    type: 'oppgave';
    navn: string;
    parent_consent_requirement: ParentConsentRequirement | null;
    sporsmal: SporsmalData[];

    // UI state (not serialised)
    expanded: boolean = false;
    activeTab: string = 'generelt';

    constructor(data?: Partial<SporreSkjemaData>) {
        this.id            = data?.id ?? 0;
        this.arrangementId = data?.arrangement_id ?? 0;
        this.type          = 'oppgave';
        this.navn          = data?.navn ?? '';
        this.parent_consent_requirement = data?.parent_consent_requirement ?? null;
        this.sporsmal      = (data?.sporsmal ?? []).map((s) => ({
            ...s,
            parent_consent_requirement: s.parent_consent_requirement ?? null,
        }));
    }

    static fromData(data: Partial<SporreSkjemaData>): SporreSkjema {
        return new SporreSkjema(data);
    }

    getId(): number {
        return this.id;
    }

    getType(): 'oppgave' {
        return this.type;
    }

    getArrangementId(): number {
        return this.arrangementId;
    }

    getNavn(): string {
        return this.navn;
    }

    getSporsmal(): SporsmalData[] {
        return this.sporsmal;
    }

    getOverskrifter(): SporsmalData[] {
        return this.sporsmal.filter(s => s.type === 'overskrift');
    }

    getAntallOverskrifter(): number {
        return this.getOverskrifter().length;
    }

    /**
     * Returns all questions grouped per overskrift.
     * Questions before the first overskrift are placed in a leading group with overskrift: null.
     * Mirrors PHP Skjema::getSporsmalPerOverskrift().
     */
    getSporsmalPerOverskrift(): GruppeData[] {
        const gruppert: GruppeData[] = [];

        this.sporsmal.forEach((sporsmal, count) => {
            if (count === 0 && sporsmal.type !== 'overskrift') {
                gruppert.push({ overskrift: null, sporsmal: [] });
            }

            if (sporsmal.type === 'overskrift') {
                gruppert.push({ overskrift: sporsmal, sporsmal: [] });
            } else {
                gruppert[gruppert.length - 1].sporsmal.push(sporsmal);
            }
        });

        return gruppert;
    }

    /**
     * Has the form been answered by the given user?
     * @override SkjemaSuper
     */
    isAnswered(_userId?: number): boolean {
        return false;
    }

    /**
     * Has the form been fully completed and approved for the given user?
     * @override SkjemaSuper
     */
    isGodkjent(_userId?: number): boolean {
        return false;
    }

    toJSON(): SporreSkjemaData {
        return {
            id:             this.id,
            arrangement_id: this.arrangementId,
            type:           this.type,
            navn:           this.navn,
            parent_consent_requirement: this.parent_consent_requirement,
            sporsmal:       this.sporsmal,
        };
    }
}
