<template>
    <div>
        <div class="section-header as-margin-bottom-space-5">
            <div class="as-padding-left-space-1">
                <h4>Oppgaver</h4>
            </div>
        </div>

        <!-- Ny oppgave -->
        <div class="as-margin-bottom-space-4 as-card-1 as-padding-space-3">
            <h4>Ny oppgave</h4>
            <div class="as-margin-top-space-4">
                <v-text-field
                    v-model="nyOppgave.navn"
                    label="Navn"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    class="as-margin-bottom-space-2 v-text-field-arr-sys"
                />
                <v-textarea
                    v-model="nyOppgave.beskrivelse"
                    label="Beskrivelse (valgfritt)"
                    variant="outlined"
                    density="comfortable"
                    rows="2"
                    hide-details="auto"
                    class="as-margin-bottom-space-2 v-text-field-arr-sys"
                />
                <v-select
                    v-model="nyOppgave.type"
                    :items="oppgaveTypeValg"
                    item-title="label"
                    item-value="value"
                    label="Type (valgfritt)"
                    variant="outlined"
                    density="comfortable"
                    clearable
                    hide-details="auto"
                    class="v-autocomplete-arr-sys"
                />
            </div>

            <v-btn
                class="v-btn-as v-btn-success as-margin-top-space-4"
                color="#000"
                rounded="large"
                variant="outlined"
                :loading="opprettLoading"
                @click="opprettOppgave"
            >
                Opprett oppgave
            </v-btn>
        </div>

        <div class="as-padding-left-space-1 as-padding-right-space-1 as-margin-bottom-space-2">
            <div v-if="hentet && oppgaver.length < 1 && !listeLoading">
                <PermanentNotification
                    typeNotification="info"
                    tittel="Ingen oppgaver ennå"
                    description="Opprett en oppgave over, og legg til skjemaer i rekkefølgen brukeren skal gjennom."
                />
            </div>
        </div>

        <template v-if="listeLoading">
            <v-skeleton-loader
                v-for="n in 2"
                :key="n"
                type="card"
                class="as-margin-bottom-space-2 skjema-skeleton"
            />
        </template>

        <div
            v-for="o in oppgaver"
            :key="o.id"
            class="as-margin-bottom-space-4 as-card-1 as-padding-space-3"
        >
            <div class="d-flex flex-wrap align-center justify-space-between gap-2">
                <h4>{{ o.name }}</h4>
                <v-btn
                    class="v-btn-as v-btn-error"
                    icon
                    variant="text"
                    size="small"
                    :loading="slettOppgaveId === o.id"
                    @click="bekreftSlettOppgave(o)"
                >
                    <v-icon>mdi-delete-outline</v-icon>
                </v-btn>
            </div>
            <div v-if="o.type || o.description">
                <span v-if="o.type">{{ typeLabel(o.type) }}</span>
                <span v-if="o.type && o.description"> · </span>
                <span v-if="o.description">{{ o.description }}</span>
            </div>
            <div class="as-margin-top-space-4">
                <p class="kjede-tittel">Skjemarekkefølge</p>
                <ol v-if="o.skjema_kjede.length" class="kjede-liste">
                    <li
                        v-for="ledd in o.skjema_kjede"
                        :key="ledd.id"
                        class="kjede-rad"
                    >
                        <span>{{ skjemaTypeLabel(ledd.skjema_type) }}: {{ skjemaNavn(ledd) }}</span>
                        <v-btn
                            size="x-small"
                            variant="text"
                            color="error"
                            :loading="slettLeddId === ledd.id"
                            @click="fjernLedd(o, ledd)"
                        >
                            Fjern
                        </v-btn>
                    </li>
                </ol>
                <p v-else class="tom-kjede">Ingen skjema i kjeden ennå.</p>

                <div class="legg-til-rad as-margin-top-space-3">
                    <v-select
                        v-model="appendModel[o.id].skjemaType"
                        :items="skjemaTypeValg"
                        item-title="label"
                        item-value="value"
                        label="Skjematype"
                        variant="outlined"
                        density="compact"
                        hide-details="auto"
                        class="felt"
                        @update:model-value="nullstillSkjemaId(o.id)"
                    />
                    <v-select
                        v-model="appendModel[o.id].skjemaId"
                        :items="skjemaIdValgFor(o.id)"
                        item-title="navn"
                        item-value="id"
                        label="Skjema"
                        variant="outlined"
                        density="compact"
                        hide-details="auto"
                        :disabled="!appendModel[o.id].skjemaType"
                        class="felt"
                    />
                    <v-btn
                        class="v-btn-as"
                        color="primary"
                        variant="flat"
                        :loading="appendLoadingId === o.id"
                        :disabled="!kanLeggeTil(o.id)"
                        @click="leggTilLedd(o)"
                    >
                        Legg til i kjeden
                    </v-btn>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { PermanentNotification } from 'ukm-components-vue3';
import {
    hentOppgaveOversikt,
    opprettOppgave as apiOpprettOppgave,
    slettOppgave as apiSlettOppgave,
    leggTilSkjemaIKjede,
    fjernSkjemaFraKjede,
    type OppgaveData,
    type OppgaveSkjemaKjedeItem,
} from '../services/oppgaveService';

const SK_SAMTYKKE = 'samtykkeskjema';
const SK_VIDERESENDING = 'ukm_videresending_skjema';
const OPP_TYPE_VIDERESENDING = 'videresending';

export default {
    components: { PermanentNotification },

    emits: ['feil'],

    data() {
        return {
            oppgaver: [] as OppgaveData[],
            skjemaValg: {} as Record<string, { id: number; navn: string }[]>,
            hentet: false,
            listeLoading: false,
            opprettLoading: false,
            appendLoadingId: null as number | null,
            slettOppgaveId: null as number | null,
            slettLeddId: null as number | null,
            nyOppgave: {
                navn: '',
                beskrivelse: '',
                type: null as string | null,
            },
            appendModel: {} as Record<number, { skjemaType: string | null; skjemaId: number | null }>,
            oppgaveTypeValg: [
                { label: 'Videresending', value: OPP_TYPE_VIDERESENDING },
            ],
            skjemaTypeValg: [
                { label: 'Samtykkeskjema', value: SK_SAMTYKKE },
                { label: 'Spørreskjema (oppgave)', value: SK_VIDERESENDING },
            ],
        };
    },

    mounted() {
        this.hentAlt();
    },

    methods: {
        typeLabel(type: string): string {
            if (type === OPP_TYPE_VIDERESENDING) {
                return 'Videresending';
            }
            return type;
        },

        skjemaTypeLabel(t: string): string {
            if (t === SK_SAMTYKKE) {
                return 'Samtykkeskjema';
            }
            if (t === SK_VIDERESENDING) {
                return 'Spørreskjema';
            }
            return t;
        },

        skjemaNavn(ledd: OppgaveSkjemaKjedeItem): string {
            const liste = this.skjemaValg[ledd.skjema_type] ?? [];
            const funnet = liste.find((x) => x.id === ledd.skjema_id);
            return funnet ? funnet.navn : `#${ledd.skjema_id}`;
        },

        nullstillSkjemaId(oppgaveId: number): void {
            this.sikreAppendModel(oppgaveId);
            this.appendModel[oppgaveId].skjemaId = null;
        },

        sikreAppendModel(oppgaveId: number): void {
            if (!this.appendModel[oppgaveId]) {
                this.appendModel[oppgaveId] = { skjemaType: null, skjemaId: null };
            }
        },

        skjemaIdValgFor(oppgaveId: number): { id: number; navn: string }[] {
            this.sikreAppendModel(oppgaveId);
            const t = this.appendModel[oppgaveId].skjemaType;
            if (!t) {
                return [];
            }
            return this.skjemaValg[t] ?? [];
        },

        kanLeggeTil(oppgaveId: number): boolean {
            this.sikreAppendModel(oppgaveId);
            const m = this.appendModel[oppgaveId];
            return !!(m.skjemaType && m.skjemaId != null && m.skjemaId > 0);
        },

        async hentAlt(): Promise<void> {
            this.listeLoading = true;
            try {
                const data = await hentOppgaveOversikt();
                this.oppgaver = data.oppgaver;
                this.skjemaValg = data.skjema_valg || {};
                this.oppgaver.forEach((o) => this.sikreAppendModel(o.id));
                this.hentet = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av oppgaver');
            } finally {
                this.listeLoading = false;
            }
        },

        async opprettOppgave(): Promise<void> {
            const navn = this.nyOppgave.navn.trim();
            if (!navn) {
                this.$emit('feil', 'Navn er påkrevd.');
                return;
            }
            this.opprettLoading = true;
            try {
                const type = this.nyOppgave.type || null;
                const besk = this.nyOppgave.beskrivelse.trim() || null;
                await apiOpprettOppgave(navn, type, besk);
                this.nyOppgave = { navn: '', beskrivelse: '', type: null };
                await this.hentAlt();
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Kunne ikke opprette oppgave');
            } finally {
                this.opprettLoading = false;
            }
        },

        bekreftSlettOppgave(o: OppgaveData): void {
            if (!window.confirm(`Slette oppgaven «${o.name}» og tilhørende kjedeelementer?`)) {
                return;
            }
            this.slettOppgave(o);
        },

        async slettOppgave(o: OppgaveData): Promise<void> {
            this.slettOppgaveId = o.id;
            try {
                await apiSlettOppgave(o.id);
                delete this.appendModel[o.id];
                await this.hentAlt();
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Kunne ikke slette oppgave');
            } finally {
                this.slettOppgaveId = null;
            }
        },

        async leggTilLedd(o: OppgaveData): Promise<void> {
            this.sikreAppendModel(o.id);
            const m = this.appendModel[o.id];
            if (!this.kanLeggeTil(o.id) || !m.skjemaType || m.skjemaId == null) {
                return;
            }
            this.appendLoadingId = o.id;
            try {
                const kjede = await leggTilSkjemaIKjede(o.id, m.skjemaType, m.skjemaId);
                const idx = this.oppgaver.findIndex((x) => x.id === o.id);
                if (idx !== -1) {
                    this.oppgaver[idx].skjema_kjede = kjede;
                }
                m.skjemaId = null;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Kunne ikke legge til skjema');
            } finally {
                this.appendLoadingId = null;
            }
        },

        async fjernLedd(o: OppgaveData, ledd: OppgaveSkjemaKjedeItem): Promise<void> {
            this.slettLeddId = ledd.id;
            try {
                const kjede = await fjernSkjemaFraKjede(ledd.id);
                const idx = this.oppgaver.findIndex((x) => x.id === o.id);
                if (idx !== -1) {
                    this.oppgaver[idx].skjema_kjede = kjede;
                }
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Kunne ikke fjerne skjema');
            } finally {
                this.slettLeddId = null;
            }
        },
    },
};
</script>

<style scoped>
.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}
.intro-tekst {
    color: var(--color-primary-grey-dark, #666);
    font-size: 0.95rem;
    margin-top: 0.5rem;
    max-width: 42rem;
}
.skjema-card {
    background: transparent;
    box-shadow: none !important;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: var(--radius-high, 12px);
}
.kjede-tittel {
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.kjede-liste {
    margin: 0;
    padding-left: 1.25rem;
}
.kjede-rad {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}
.tom-kjede {
    color: var(--color-primary-grey-dark, #666);
    font-style: italic;
}
.legg-til-rad {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
}
.legg-til-rad .felt {
    flex: 1 1 180px;
    min-width: 160px;
}
.skjema-skeleton {
    border-radius: var(--radius-high) !important;
}
</style>
