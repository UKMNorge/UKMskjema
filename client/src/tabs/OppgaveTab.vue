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
                    label="Type"
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
                <p v-if="o.skjema_kjede.length > 0" class="kjede-hjelp">
                    Dra et skjema for å flytte det. Slipp på et annet for å bytte plass.
                </p>
                <div
                    v-if="o.skjema_kjede.length"
                    class="kjede-baand"
                    :class="{ 'kjede-baand--busy': reorderOppgaveId === o.id }"
                >
                    <div
                        v-for="(ledd, index) in o.skjema_kjede"
                        :key="ledd.id"
                        class="kjede-gruppe"
                    >
                        <span
                            v-if="index > 0"
                            class="kjede-separator"
                            aria-hidden="true"
                        >→</span>
                        <div
                            class="kjede-chip"
                            :class="{
                                'kjede-chip--dragging': dragKilde?.radId === ledd.id,
                                'kjede-chip--over': dragOverRadId === ledd.id && dragKilde?.radId !== ledd.id,
                                'chip-sporsmal': ledd.skjema_type === 'ukm_videresending_skjema',
                                'chip-samtykke': ledd.skjema_type === 'samtykkeskjema'
                            }"
                            draggable="true"
                            @dragstart="onKjedeDragStart(o, ledd, $event)"
                            @dragend="onKjedeDragEnd"
                            @dragover.prevent="onKjedeDragOver(o, ledd, $event)"
                            @dragleave="onKjedeDragLeave(ledd)"
                            @drop.prevent="onKjedeDrop(o, ledd, $event)"
                        >
                            <v-icon size="small" class="kjede-chip__grip" aria-hidden="true">
                                mdi-drag-vertical
                            </v-icon>
                            <span class="kjede-chip__tekst" :title="skjemaFullTekst(ledd)">
                                <span class="kjede-chip__type">{{ skjemaTypeLabel(ledd.skjema_type) }}</span>
                                <span class="kjede-chip__navn">{{ skjemaNavn(ledd) }}</span>
                            </span>
                            <v-btn
                                icon
                                size="x-small"
                                variant="text"
                                color="error"
                                class="kjede-chip__fjern"
                                :loading="slettLeddId === ledd.id"
                                tabindex="-1"
                                @click.stop="fjernLedd(o, ledd)"
                            >
                                <v-icon size="small">mdi-close</v-icon>
                            </v-btn>
                        </div>
                    </div>
                </div>
                <p v-else class="tom-kjede">Ingen skjema i kjeden ennå.</p>

                <div class="legg-til-rad as-margin-top-space-3">
                    <v-select
                        v-model="appendModel[o.id].skjemaType"
                        :items="skjemaTypeValg"
                        item-title="label"
                        item-value="value"
                        label="Skjematype"
                        variant="outlined"
                        hide-details="auto"
                        class="felt v-autocomplete-arr-sys"
                        @update:model-value="nullstillSkjemaId(o.id)"
                    />
                    <v-select
                        v-model="appendModel[o.id].skjemaId"
                        :items="skjemaIdValgFor(o.id)"
                        item-title="navn"
                        item-value="id"
                        label="Skjema"
                        variant="outlined"
                        hide-details="auto"
                        :disabled="!appendModel[o.id].skjemaType"
                        class="felt v-autocomplete-arr-sys"
                    />
                    <div class="as-margin-auto">
                        <v-btn
                            class="v-btn-as v-btn-success as-margin-right-space-2"
                            rounded="large"
                            size="large"
                            variant="outlined"
                            :loading="appendLoadingId === o.id"
                            :disabled="!kanLeggeTil(o.id)"
                            @click="leggTilLedd(o)"
                        >
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </div>
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
    reorderOppgaveKjede,
    type OppgaveData,
    type OppgaveSkjemaKjedeItem,
} from '../services/oppgaveService';

const SK_SAMTYKKE = 'samtykkeskjema';
const SK_VIDERESENDING = 'ukm_videresending_skjema';
const OPP_TYPE_VIDERESENDING = 'videresending';
const OPP_TYPE_REISELEDERE = 'reiseledere';
const OPP_TYPE_FYLKESKONTAKTER = 'fylkeskontakter';

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
            reorderOppgaveId: null as number | null,
            dragKilde: null as { oppgaveId: number; radId: number } | null,
            dragOverRadId: null as number | null,
            nyOppgave: {
                navn: '',
                beskrivelse: '',
                type: null as string | null,
            },
            appendModel: {} as Record<number, { skjemaType: string | null; skjemaId: number | null }>,
            oppgaveTypeValg: [
                { label: 'Videresending', value: OPP_TYPE_VIDERESENDING },
                { label: 'Reiseledere', value: OPP_TYPE_REISELEDERE },
                { label: 'Fylkeskontakter', value: OPP_TYPE_FYLKESKONTAKTER },
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
            if (type === OPP_TYPE_REISELEDERE) {
                return 'Reiseledere';
            }
            if (type === OPP_TYPE_FYLKESKONTAKTER) {
                return 'Fylkeskontakter';
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

        skjemaFullTekst(ledd: OppgaveSkjemaKjedeItem): string {
            return `${this.skjemaTypeLabel(ledd.skjema_type)}: ${this.skjemaNavn(ledd)}`;
        },

        onKjedeDragStart(o: OppgaveData, ledd: OppgaveSkjemaKjedeItem, e: DragEvent): void {
            if (this.reorderOppgaveId === o.id) {
                e.preventDefault();
                return;
            }
            this.dragKilde = { oppgaveId: o.id, radId: ledd.id };
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', String(ledd.id));
            }
        },

        onKjedeDragEnd(): void {
            this.dragKilde = null;
            this.dragOverRadId = null;
        },

        onKjedeDragOver(o: OppgaveData, ledd: OppgaveSkjemaKjedeItem, e: DragEvent): void {
            if (!this.dragKilde || this.dragKilde.oppgaveId !== o.id) {
                return;
            }
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
            this.dragOverRadId = ledd.id;
        },

        onKjedeDragLeave(ledd: OppgaveSkjemaKjedeItem): void {
            if (this.dragOverRadId === ledd.id) {
                this.dragOverRadId = null;
            }
        },

        async onKjedeDrop(o: OppgaveData, targetLedd: OppgaveSkjemaKjedeItem, e: DragEvent): Promise<void> {
            const kilde = this.dragKilde;
            this.dragOverRadId = null;
            if (!kilde || kilde.oppgaveId !== o.id) {
                return;
            }
            const fromId = kilde.radId;
            const toId = targetLedd.id;
            if (fromId === toId) {
                return;
            }

            const oIdx = this.oppgaver.findIndex((x) => x.id === o.id);
            if (oIdx === -1) {
                return;
            }
            const kjede = [...this.oppgaver[oIdx].skjema_kjede];
            const iFrom = kjede.findIndex((x) => x.id === fromId);
            const iTo = kjede.findIndex((x) => x.id === toId);
            if (iFrom === -1 || iTo === -1) {
                return;
            }
            const [flyttet] = kjede.splice(iFrom, 1);
            kjede.splice(iTo, 0, flyttet);
            const radIds = kjede.map((x) => x.id);

            this.reorderOppgaveId = o.id;
            try {
                const oppdatert = await reorderOppgaveKjede(o.id, radIds);
                this.oppgaver[oIdx].skjema_kjede = oppdatert;
            } catch (err: any) {
                this.$emit('feil', err.message ?? 'Kunne ikke endre rekkefølge');
            } finally {
                this.reorderOppgaveId = null;
                this.dragKilde = null;
            }
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
    margin-bottom: 0.35rem;
}
.kjede-hjelp {
    font-size: 0.875rem;
    color: var(--color-primary-grey-dark, #666);
    margin-bottom: 0.75rem;
    max-width: 40rem;
}
.kjede-baand {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.15rem;
}
.kjede-gruppe {
    display: contents;
    align-items: center;
    gap: 0.15rem;
}
.kjede-baand--busy {
    opacity: 0.65;
    pointer-events: none;
}
.kjede-separator {
    color: rgba(0, 0, 0, 0.4);
    font-weight: 700;
    font-size: 1rem;
    user-select: none;
    line-height: 1;
    padding: 0 0.1rem;
}
.kjede-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    max-width: min(100%, 17.5rem);
    padding: 0.3rem 0.35rem 0.3rem 0.25rem;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: var(--radius-high, 10px);
    background: rgba(255, 255, 255, 0.95);
    cursor: grab;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        opacity 0.15s ease;
}
.kjede-chip:active {
    cursor: grabbing;
}
.kjede-chip--dragging {
    opacity: 0.45;
}
.kjede-chip--over {
    border-color: rgb(var(--v-theme-primary, 25 118 210));
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.22);
}
.kjede-chip.chip-sporsmal {
    background-color: var(--as-color-primary-warning-lightest);
}
.kjede-chip.chip-samtykke {
    background-color: var(--as-color-primary-info-lightest);
}
.kjede-chip__grip {
    opacity: 0.5;
    flex-shrink: 0;
}
.kjede-chip__tekst {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.05rem;
    line-height: 1.2;
}
.kjede-chip__type {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.65;
}
.kjede-chip__navn {
    font-size: 12px;
    font-weight: 800;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.kjede-chip__fjern {
    flex-shrink: 0;
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
