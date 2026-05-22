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

        <div
            v-else-if="respondenter.length"
            class="deltaker-liste"
        >
            <div
                v-for="r in respondenter"
                :key="r.id"
                class="deltaker-rad deltaker-rad--klikkbar"
                role="button"
                tabindex="0"
                @click="apneRespondent(r)"
                @keyup.enter="apneRespondent(r)"
            >
                <span class="deltaker-rad__navn">{{ r.navn }} {{ r.etternavn }}</span>
                <v-icon size="small" class="deltaker-rad__pil">mdi-chevron-right</v-icon>
            </div>
        </div>
        <p v-else class="tom-kjede">Ingen respondenter på denne oppgaven ennå.</p>
    </div>
</template>

<script lang="ts">
import { hentAlleRespondenter } from '../services/oppgaveService';
import OppgaveRespondent, { type OppgaveRespondentData } from '../objects/OppgaveRespondent';

function tilRespondentData(r: OppgaveRespondent): OppgaveRespondentData {
    return {
        id: r.id,
        navn: r.navn,
        etternavn: r.etternavn,
        mobil: r.mobil,
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

    methods: {
        async hentRespondenter(): Promise<void> {
            if (!this.oppgaveId) {
                this.respondenter = [];
                return;
            }
            this.loading = true;
            try {
                const hentet = await hentAlleRespondenter(this.oppgaveId);
                this.respondenter = hentet.map(tilRespondentData);
            } catch (e: any) {
                this.respondenter = [];
                this.$emit('feil', e.message ?? 'Kunne ikke hente respondenter');
            } finally {
                this.loading = false;
            }
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
.deltaker-rad__navn {
    font-weight: 700;
    font-size: 0.95rem;
    min-width: 0;
}
.deltaker-rad__pil {
    opacity: 0.45;
    flex-shrink: 0;
}
</style>
