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
