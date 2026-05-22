<template>
    <div class="oppgave-deltakere as-margin-top-space-4">
        <p class="kjede-tittel">Respondenter</p>
        <p class="kjede-hjelp">
            Klikk en respondent for å se oppgavelisten (skjemasvar).
        </p>

        <v-skeleton-loader
            v-if="loading"
            type="list-item-two-line"
            class="as-margin-bottom-space-2"
        />

        <template v-else-if="respondenter.length">
            <div class="status-oppsummering as-margin-bottom-space-3">
                <v-chip
                    class="status-oppsummering__chip"
                    size="small"
                    variant="tonal"
                    color="primary"
                >
                    Totalt: {{ respondenter.length }}
                </v-chip>
                <v-chip
                    v-if="antallLaster > 0"
                    class="status-oppsummering__chip"
                    size="small"
                    variant="tonal"
                    color="grey"
                >
                    Laster: {{ antallLaster }}
                </v-chip>
                <v-chip
                    v-for="s in statusOppsummeringTyper"
                    :key="s.status"
                    class="status-oppsummering__chip"
                    size="small"
                    variant="tonal"
                    :color="s.color"
                >
                    {{ s.label }}: {{ tellStatus(s.status) }}
                </v-chip>
            </div>

            <div class="deltaker-liste">
            <div
                v-for="r in respondenter"
                :key="r.id"
                class="deltaker-rad deltaker-rad--klikkbar"
                role="button"
                tabindex="0"
                @click="apneRespondent(r)"
                @keyup.enter="apneRespondent(r)"
            >
                <div class="deltaker-rad__hoved">
                    <span class="deltaker-rad__navn">{{ r.navn }} {{ r.etternavn }}</span>
                </div>
                <div class="deltaker-rad__hoyre">
                    <v-progress-circular
                        v-if="r.svar_status === null"
                        indeterminate
                        size="18"
                        width="2"
                        color="primary"
                    />
                    <v-chip
                        v-else
                        size="small"
                        variant="tonal"
                        :color="svarStatusColor(r.svar_status)"
                    >
                        {{ svarStatusLabel(r.svar_status) }}
                    </v-chip>
                    <v-icon size="small" class="deltaker-rad__pil">mdi-chevron-right</v-icon>
                </div>
            </div>
            </div>
        </template>
        <p v-else class="tom-kjede">Ingen respondenter på denne oppgaven ennå.</p>
    </div>
</template>

<script lang="ts">
import { hentAlleRespondenter, hentRespondentSvarStatus } from '../services/oppgaveService';
import OppgaveRespondent, {
    type OppgaveRespondentData,
    type OppgaveSvarStatus,
    OPPGAVE_SVAR_STATUS_FULLFORT,
    OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT,
    OPPGAVE_SVAR_STATUS_PABEGYNT,
    OPPGAVE_SVAR_STATUS_UKJENT,
    OPPGAVE_SVAR_STATUS_VENTER_FORESATT,
    oppgaveSvarStatusColor,
    oppgaveSvarStatusLabel,
} from '../objects/OppgaveRespondent';

const STATUS_OPPSUMMERING_TYPER: { status: OppgaveSvarStatus; label: string; color: string }[] = [
    { status: OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT) },
    { status: OPPGAVE_SVAR_STATUS_PABEGYNT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_PABEGYNT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_PABEGYNT) },
    { status: OPPGAVE_SVAR_STATUS_VENTER_FORESATT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_VENTER_FORESATT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_VENTER_FORESATT) },
    { status: OPPGAVE_SVAR_STATUS_FULLFORT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_FULLFORT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_FULLFORT) },
    { status: OPPGAVE_SVAR_STATUS_UKJENT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_UKJENT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_UKJENT) },
];

function tilRespondentData(r: OppgaveRespondent, svarStatus: OppgaveSvarStatus | null = null): OppgaveRespondentData {
    return {
        id: r.id,
        navn: r.navn,
        etternavn: r.etternavn,
        mobil: r.mobil,
        svar_status: svarStatus,
    };
}

export default {
    props: {
        oppgaveId: {
            type: Number,
            required: true,
        },
    },

    emits: ['open-svar', 'feil'],

    data() {
        return {
            loading: false,
            respondenter: [] as OppgaveRespondentData[],
            statusHentingId: 0,
        };
    },

    watch: {
        oppgaveId: {
            immediate: true,
            handler() {
                this.hentRespondenter();
            },
        },
    },

    computed: {
        statusOppsummeringTyper(): typeof STATUS_OPPSUMMERING_TYPER {
            return STATUS_OPPSUMMERING_TYPER;
        },

        antallLaster(): number {
            return this.respondenter.filter((r) => r.svar_status === null).length;
        },
    },

    methods: {
        tellStatus(status: OppgaveSvarStatus): number {
            return this.respondenter.filter((r) => r.svar_status === status).length;
        },
        async hentRespondenter(): Promise<void> {
            if (!this.oppgaveId) {
                this.respondenter = [];
                return;
            }
            this.loading = true;
            try {
                const hentet = await hentAlleRespondenter(this.oppgaveId);
                this.respondenter = hentet.map((r) => tilRespondentData(r, null));
                this.loading = false;
                await this.hentSvarStatusForAlle();
            } catch (e: any) {
                this.respondenter = [];
                this.$emit('feil', e.message ?? 'Kunne ikke hente respondenter');
                this.loading = false;
            }
        },

        async hentSvarStatusForAlle(): Promise<void> {
            const oppgaveId = this.oppgaveId;
            const hentingId = ++this.statusHentingId;
            await Promise.all(
                this.respondenter.map(async (respondent) => {
                    try {
                        const status = await hentRespondentSvarStatus(oppgaveId, respondent.id);
                        if (hentingId !== this.statusHentingId) {
                            return;
                        }
                        respondent.svar_status = status;
                    } catch {
                        if (hentingId === this.statusHentingId) {
                            respondent.svar_status = OPPGAVE_SVAR_STATUS_UKJENT;
                        }
                    }
                })
            );
        },

        svarStatusLabel(status: OppgaveSvarStatus | null | undefined): string {
            if (status === null || status === undefined) {
                return '';
            }
            return oppgaveSvarStatusLabel(status);
        },

        svarStatusColor(status: OppgaveSvarStatus | null | undefined): string {
            if (status === null || status === undefined) {
                return 'grey';
            }
            return oppgaveSvarStatusColor(status);
        },

        apneRespondent(respondent: OppgaveRespondentData): void {
            this.$emit('open-svar', OppgaveRespondent.fromAjax({ ...respondent }));
        },
    },
};
</script>

<style scoped>
.kjede-tittel {
    font-weight: 600;
    margin-bottom: 0.35rem;
}
.kjede-hjelp {
    font-size: 0.875rem;
    color: var(--color-primary-grey-dark, #666);
    margin-bottom: 0.75rem;
    max-width: 40rem;
}
.tom-kjede {
    color: var(--color-primary-grey-dark, #666);
    font-style: italic;
}
.oppgave-deltakere {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    padding-top: 1rem;
}
.status-oppsummering {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}
.status-oppsummering__chip {
    font-weight: 600;
}
.deltaker-liste {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.deltaker-rad {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.6rem 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: var(--radius-high, 10px);
    background: rgba(255, 255, 255, 0.6);
}
.deltaker-rad--klikkbar {
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
}
.deltaker-rad--klikkbar:hover,
.deltaker-rad--klikkbar:focus-visible {
    border-color: rgba(25, 118, 210, 0.45);
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12);
    background: rgba(255, 255, 255, 0.95);
    outline: none;
}
.deltaker-rad__hoved {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}
.deltaker-rad__navn {
    font-weight: 700;
    font-size: 0.95rem;
    min-width: 0;
}
.deltaker-rad__hoyre {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}
.deltaker-rad__pil {
    opacity: 0.45;
    flex-shrink: 0;
}
</style>
