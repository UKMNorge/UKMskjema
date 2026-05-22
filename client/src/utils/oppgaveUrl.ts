import { Director } from 'ukm-spa/Director';

/** v-tabs index for «Oppgaver» in App.vue */
export const OPPGAVE_TAB_INDEX = 2;

export const PARAM_OPGAVE_ID = 'oppgave_id';
export const PARAM_RESPONDENT_ID = 'respondent_id';

export interface RespondentSvarUrlParams {
    oppgaveId: number;
    respondentId: number;
}

function getDirector(): Director {
    const w = window as Window & { director?: Director };
    return w.director ?? new Director();
}

export function readRespondentSvarFromUrl(): RespondentSvarUrlParams | null {
    const director = getDirector();
    const oppgaveId = Number(director.getParam(PARAM_OPGAVE_ID));
    const respondentId = Number(director.getParam(PARAM_RESPONDENT_ID));
    if (oppgaveId > 0 && respondentId > 0) {
        return { oppgaveId, respondentId };
    }
    return null;
}

export function buildRespondentSvarUrl(oppgaveId: number, respondentId: number): string {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', String(OPPGAVE_TAB_INDEX));
    params.set(PARAM_OPGAVE_ID, String(oppgaveId));
    params.set(PARAM_RESPONDENT_ID, String(respondentId));
    return `${window.location.pathname}?${params.toString()}`;
}

export function setRespondentSvarUrl(oppgaveId: number, respondentId: number): void {
    const director = getDirector();
    director.addParam('tab', String(OPPGAVE_TAB_INDEX));
    director.addParam(PARAM_OPGAVE_ID, String(oppgaveId));
    director.addParam(PARAM_RESPONDENT_ID, String(respondentId));
}

export function clearRespondentSvarUrl(): void {
    const params = new URLSearchParams(window.location.search);
    params.delete(PARAM_OPGAVE_ID);
    params.delete(PARAM_RESPONDENT_ID);
    const qs = params.toString();
    const url = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
    window.history.replaceState({}, '', url);
}

export function openRespondentSvarWindow(oppgaveId: number, respondentId: number): void {
    window.open(buildRespondentSvarUrl(oppgaveId, respondentId), '_blank', 'noopener');
}

export function ensureOppgaveTabInUrl(): void {
    getDirector().addParam('tab', String(OPPGAVE_TAB_INDEX));
}
