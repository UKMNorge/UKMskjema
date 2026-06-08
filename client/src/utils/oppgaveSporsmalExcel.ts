import { utils, writeFileXLSX } from 'xlsx';
import type { OppgaveRespondentData } from '@/objects/OppgaveRespondent';
import { oppgaveSvarStatusLabel } from '@/objects/OppgaveRespondent';
import type { OppgaveSporsmalValg } from '@/services/oppgaveService';

type SvarLinje = { label: string; value: string };

function sanitizeSheetName(name: string): string {
    return name.replace(/[:\\/?*[\]]/g, '').substring(0, 31);
}

function uniktArkNavn(base: string, brukte: Set<string>): string {
    const renset = sanitizeSheetName(base.trim() || 'Respondent');
    if (!brukte.has(renset)) {
        brukte.add(renset);
        return renset;
    }

    let i = 2;
    while (true) {
        const suffix = ` (${i})`;
        const kort = sanitizeSheetName(renset.substring(0, Math.max(1, 31 - suffix.length)) + suffix);
        if (!brukte.has(kort)) {
            brukte.add(kort);
            return kort;
        }
        i += 1;
    }
}

function respondentNavn(respondent: OppgaveRespondentData): string {
    return `${respondent.navn} ${respondent.etternavn}`.trim();
}

function statusTekst(respondent: OppgaveRespondentData): string {
    if (respondent.svar_status === null || respondent.svar_status === undefined) {
        return '';
    }
    return oppgaveSvarStatusLabel(respondent.svar_status);
}

/** Samme linjer som vises i deltaker-rad__svar-linjer i UI. */
function hentSvarLinjer(respondent: OppgaveRespondentData): SvarLinje[] {
    if (!respondent.sporsmal_svar) {
        return [{ label: '', value: '—' }];
    }
    return respondent.sporsmal_svar.linjer.map((linje) => ({
        label: linje.label ?? '',
        value: linje.value ?? '',
    }));
}

function svarLinjeFeltNavn(linje: SvarLinje): string {
    return linje.label.trim() || 'Svar';
}

/** Verdi slik den vises i deltaker-rad__svar-linjer (Lenke → filsti). */
function svarLinjeVerdiForExcel(linje: SvarLinje): string {
    const value = (linje.value ?? '').trim();
    if (linje.label === 'Lenke' && value.startsWith('/')) {
        return value;
    }
    return value;
}

function samleSvarKolonner(respondenter: OppgaveRespondentData[]): string[] {
    const kolonner: string[] = [];
    const sett = new Set<string>();

    for (const respondent of respondenter) {
        for (const linje of hentSvarLinjer(respondent)) {
            const navn = svarLinjeFeltNavn(linje);
            if (!sett.has(navn)) {
                sett.add(navn);
                kolonner.push(navn);
            }
        }
    }

    return kolonner;
}

function svarLinjerSomKolonner(respondent: OppgaveRespondentData): Record<string, string> {
    const result: Record<string, string> = {};

    for (const linje of hentSvarLinjer(respondent)) {
        result[svarLinjeFeltNavn(linje)] = svarLinjeVerdiForExcel(linje);
    }

    return result;
}

function lagRespondentTabell(
    respondent: OppgaveRespondentData,
    sporsmal: OppgaveSporsmalValg
): string[][] {
    const rader: string[][] = [['Felt', 'Verdi']];

    rader.push(['Navn', respondentNavn(respondent)]);
    rader.push(['Mobil', respondent.mobil ?? '']);

    if (respondent.fylke) {
        rader.push(['Fylke', respondent.fylke]);
    }
    if (respondent.arrangement) {
        rader.push(['Arrangement', respondent.arrangement]);
    }

    rader.push(['Status', statusTekst(respondent)]);
    rader.push(['Spørsmål', sporsmal.tittel]);

    for (const linje of hentSvarLinjer(respondent)) {
        rader.push([svarLinjeFeltNavn(linje), svarLinjeVerdiForExcel(linje)]);
    }

    if (respondent.sporsmal_svar?.foresatt_godkjent === true) {
        rader.push(['Foresatt godkjent', 'Ja']);
    } else if (respondent.sporsmal_svar?.foresatt_godkjent === false) {
        rader.push(['Foresatt godkjent', 'Nei']);
    }

    return rader;
}

function settKolonnebredder(ws: import('xlsx').WorkSheet, rader: string[][]): void {
    const bredde = rader.reduce(
        (acc, rad) => {
            rad.forEach((celle, idx) => {
                acc[idx] = Math.max(acc[idx] ?? 0, String(celle ?? '').length);
            });
            return acc;
        },
        {} as Record<number, number>
    );

    ws['!cols'] = Object.keys(bredde).map((key) => ({
        wch: (bredde[Number(key)] ?? 10) + 2,
    }));
}

function lagFilnavn(sporsmal: OppgaveSporsmalValg): string {
    const dato = new Date();
    const datoStr = dato.toISOString().slice(0, 10);
    const sporsmalNavn = sporsmal.tittel
        .trim()
        .replace(/[^a-zA-Z0-9æøåÆØÅ _-]/g, '')
        .replace(/\s+/g, '_')
        .substring(0, 40);
    return `oppgave_svar_${sporsmalNavn || 'sporsmal'}_${datoStr}.xlsx`;
}

export function lastNedSporsmalSvarExcel(
    respondenter: OppgaveRespondentData[],
    sporsmal: OppgaveSporsmalValg
): void {
    const klare = respondenter.filter((r) => r.sporsmal_svar !== null && r.sporsmal_svar !== undefined);
    if (klare.length === 0) {
        return;
    }

    const wb = utils.book_new();
    const brukteNavn = new Set<string>();
    const svarKolonner = samleSvarKolonner(klare);
    const grunnKolonner = ['Navn', 'Mobil', 'Fylke', 'Arrangement', 'Status', 'Spørsmål'];

    const oversikt = klare.map((respondent) => ({
        Navn: respondentNavn(respondent),
        Mobil: respondent.mobil ?? '',
        Fylke: respondent.fylke ?? '',
        Arrangement: respondent.arrangement ?? '',
        Status: statusTekst(respondent),
        Spørsmål: sporsmal.tittel,
        ...svarLinjerSomKolonner(respondent),
    }));

    const oversiktWs = utils.json_to_sheet(oversikt);
    const oversiktHeader = [...grunnKolonner, ...svarKolonner];
    settKolonnebredder(
        oversiktWs,
        [
            oversiktHeader,
            ...oversikt.map((rad) =>
                oversiktHeader.map((kolonne) => String((rad as Record<string, string>)[kolonne] ?? ''))
            ),
        ]
    );
    utils.book_append_sheet(wb, oversiktWs, 'Oversikt');

    for (const respondent of klare) {
        const tabell = lagRespondentTabell(respondent, sporsmal);
        const ws = utils.aoa_to_sheet(tabell);
        settKolonnebredder(ws, tabell);

        const arkNavn = uniktArkNavn(respondentNavn(respondent) || respondent.mobil, brukteNavn);
        utils.book_append_sheet(wb, ws, arkNavn);
    }

    writeFileXLSX(wb, lagFilnavn(sporsmal));
}
