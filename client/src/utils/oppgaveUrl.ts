import { Director } from 'ukm-spa/Director';

/** v-tabs index for «Oppgaver» in App.vue */
export const OPPGAVE_TAB_INDEX = 2;

export const PARAM_OPGAVE_ID = 'oppgave_id';
export const PARAM_PHONE = 'phone';

export interface RespondentSvarUrlParams {
    oppgaveId: number;
    phone: string;
}

function getDirector(): Director {
    const w = window as Window & { director?: Director };
    return w.director ?? new Director();
}

export function readRespondentSvarFromUrl(): RespondentSvarUrlParams | null {
    const director = getDirector();
    const oppgaveId = Number(director.getParam(PARAM_OPGAVE_ID));
    const phone = (director.getParam(PARAM_PHONE) ?? '').trim();
    if (oppgaveId > 0 && phone.length > 0) {
        return { oppgaveId, phone };
    }
    return null;
}

export function buildRespondentSvarUrl(oppgaveId: number, phone: string): string {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', String(OPPGAVE_TAB_INDEX));
    params.set(PARAM_OPGAVE_ID, String(oppgaveId));
    params.set(PARAM_PHONE, phone.trim());
    return `${window.location.pathname}?${params.toString()}`;
}

export function setRespondentSvarUrl(oppgaveId: number, phone: string): void {
    const director = getDirector();
    director.addParam('tab', String(OPPGAVE_TAB_INDEX));
    director.addParam(PARAM_OPGAVE_ID, String(oppgaveId));
    director.addParam(PARAM_PHONE, phone.trim());
}

export function clearRespondentSvarUrl(): void {
    const params = new URLSearchParams(window.location.search);
    params.delete(PARAM_OPGAVE_ID);
    params.delete(PARAM_PHONE);
    const qs = params.toString();
    const url = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
    window.history.replaceState({}, '', url);
}

export function openRespondentSvarWindow(oppgaveId: number, phone: string): void {
    window.open(buildRespondentSvarUrl(oppgaveId, phone), '_blank', 'noopener');
}

export function ensureOppgaveTabInUrl(): void {
    getDirector().addParam('tab', String(OPPGAVE_TAB_INDEX));
}
