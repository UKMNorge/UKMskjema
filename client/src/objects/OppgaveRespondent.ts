/** Delta-bruker som respondent på en oppgave (fra getAlleRespondenter). */
export interface OppgaveRespondentData {
    id: number;
    navn: string;
    etternavn: string;
    mobil: string;
}

export default class OppgaveRespondent {
    id: number;
    navn: string;
    etternavn: string;
    mobil: string;

    constructor(data?: Partial<OppgaveRespondentData>) {
        this.id = data?.id ?? 0;
        this.navn = data?.navn ?? '';
        this.etternavn = data?.etternavn ?? '';
        this.mobil = data?.mobil ?? '';
    }

    static fromAjax(row: Partial<OppgaveRespondentData> | Record<string, unknown>): OppgaveRespondent {
        const data = row as Record<string, unknown>;
        return new OppgaveRespondent({
            id: Number(data.id) || 0,
            navn: String(data.navn ?? ''),
            etternavn: String(data.etternavn ?? ''),
            mobil: String(data.mobil ?? ''),
        });
    }

    getNavnFullt(): string {
        return `${this.navn} ${this.etternavn}`.trim();
    }
}
