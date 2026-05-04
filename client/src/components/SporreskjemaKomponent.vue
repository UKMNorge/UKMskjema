<template>
    <div class="as-card-1 nop-impt as-margin-bottom-space-2">

        <!-- ── Header row (clickable) ─────────────────────────────── -->
        <v-list-item
            @click="skjema.expanded = !skjema.expanded"
            class="skjema-item nop-impt as-card-1 as-padding-space-3"
        >
            <div class="d-flex justify-space-between align-center">
                <v-list-item-title class="text-h6">
                    {{ skjema.navn }}
                </v-list-item-title>
            </div>

            <template v-slot:prepend>
                <v-avatar color="grey-lighten-1">
                    <v-icon color="white">mdi-help-circle-outline</v-icon>
                </v-avatar>
            </template>
        </v-list-item>

        <!-- ── Expandable content ──────────────────────────────────── -->
        <v-expand-transition>
            <div v-if="skjema.expanded" class="as-padding-space-3 extended-skjema-content" @click.stop>

                <!-- ════ NEW SKJEMA (id == -1) ════ -->
                <template v-if="skjema.id === -1">
                    <div class="col-xs-12 nop-impt">
                        <div class="tidspunkt-tittel as-margin-bottom-space-3">
                            <h5>Nytt spørreskjema</h5>
                        </div>
                        <div class="col-xs-10 nop-impt">
                            <v-text-field
                                v-model="skjema.navn"
                                label="Navn på skjema"
                                placeholder="F.eks. Spørreskjema for deltakere 2026"
                                variant="outlined"
                                class="v-text-field-arr-sys"
                                density="comfortable"
                                hide-details="auto"
                                autofocus
                                @keyup.enter="skjema.navn.trim() && $emit('opprett', skjema)"
                            />
                        </div>
                        <div class="col-xs-12 nop-impt as-margin-top-space-3">
                            <v-btn
                                class="v-btn-as v-btn-success as-margin-right-space-1"
                                rounded="large"
                                size="large"
                                variant="outlined"
                                :loading="loading"
                                :disabled="!skjema.navn.trim()"
                                @click="$emit('opprett', skjema)"
                            >
                                <v-icon start>mdi-check</v-icon>
                                Opprett
                            </v-btn>
                            <v-btn
                                class="v-btn-as v-btn-grey"
                                rounded="large"
                                size="large"
                                variant="outlined"
                                @click="$emit('fjern')"
                            >
                                Avbryt
                            </v-btn>
                        </div>
                    </div>
                </template>

                <!-- ════ EXISTING SKJEMA ════ -->
                <template v-else>
                    <div class="col-xs-12 nop-impt">

                        <!-- Tabs -->
                        <v-tabs v-model="skjema.activeTab" color="primary" align-tabs="start">
                            <v-tab value="generelt">
                                <v-icon start>mdi-information-outline</v-icon>
                                Generelt
                            </v-tab>
                            <v-tab value="sporsmal">
                                <v-icon start>mdi-comment-question-outline</v-icon>
                                Spørsmål
                                <v-chip v-if="skjema.sporsmal.length" size="x-small" class="ml-2" color="primary">
                                    {{ skjema.sporsmal.length }}
                                </v-chip>
                            </v-tab>
                        </v-tabs>

                        <v-divider class="as-margin-bottom-space-3" />

                        <v-tabs-window v-model="skjema.activeTab">

                            <!-- ── TAB: Generelt ──────────────── -->
                            <v-tabs-window-item value="generelt">
                                <div class="col-xs-12 nop-impt">
                                    <div class="tidspunkt-tittel as-margin-bottom-space-3">
                                        <h5>Grunnleggende informasjon</h5>
                                    </div>
                                    <div class="col-xs-10 nop-impt">
                                        <v-text-field
                                            v-model="skjema.navn"
                                            label="Navn på skjema"
                                            variant="outlined"
                                            class="v-text-field-arr-sys"
                                            density="comfortable"
                                            hide-details="auto"
                                        />
                                    </div>
                                    <div class="col-xs-12 nop-impt as-margin-top-space-2 item-id-label">
                                        ID #{{ skjema.id }} · Arrangement ID #{{ skjema.arrangementId }}
                                    </div>
                                </div>
                            </v-tabs-window-item>

                            <!-- ── TAB: Spørsmål ──────────────── -->
                            <v-tabs-window-item value="sporsmal">
                                <div class="col-xs-12 nop-impt">

                                    <div class="col-xs-12 nop-impt as-margin-bottom-space-3 tab-section-header">
                                        <div class="tidspunkt-tittel">
                                            <h5>Spørsmål i skjemaet</h5>
                                            <p v-if="skjema.sporsmal.length" class="sporsmal-dnd-hint item-id-label mt-1 mb-0">
                                                Dra håndtaket til venstre for å endre rekkefølge. Husk «Lagre skjemaet».
                                            </p>
                                        </div>
                                        <v-btn
                                            class="v-btn-as v-btn-bla"
                                            rounded="large"
                                            size="large"
                                            variant="outlined"
                                            @click="visNyttSporsmalForm = !visNyttSporsmalForm"
                                        >
                                            <v-icon start>mdi-plus</v-icon>
                                            Legg til spørsmål
                                        </v-btn>
                                    </div>

                                    <!-- Add-question form -->
                                    <v-expand-transition>
                                        <div v-if="visNyttSporsmalForm" class="col-xs-12 col-xs-inner-box as-margin-bottom-space-3 nop-impt-fix">
                                            <div class="tidspunkt-tittel as-margin-bottom-space-3">
                                                <h5>Nytt spørsmål</h5>
                                            </div>
                                            <div class="col-xs-12 nop-impt">
                                                <div class="col-xs-3 nop-impt as-margin-right-space-2">
                                                    <v-select
                                                        v-model="nyttSporsmal.type"
                                                        :items="sporsmalTypeOptions"
                                                        item-title="label"
                                                        item-value="value"
                                                        label="Type *"
                                                        variant="outlined"
                                                        class="v-autocomplete-arr-sys"
                                                        hide-details="auto"
                                                    />
                                                </div>
                                                <div class="col-xs-7 nop-impt">
                                                    <v-text-field
                                                        v-model="nyttSporsmal.tittel"
                                                        label="Tittel / spørsmålstekst *"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details="auto"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-xs-12 nop-impt as-margin-top-space-2">
                                                <v-textarea
                                                    v-model="nyttSporsmal.tekst"
                                                    label="Hjelpetekst (valgfritt)"
                                                    variant="outlined"
                                                    class="v-text-field-arr-sys"
                                                    density="compact"
                                                    rows="2"
                                                    hide-details="auto"
                                                />
                                            </div>
                                            <div class="col-xs-12 nop-impt as-margin-top-space-3">
                                                <v-btn
                                                    class="v-btn-as v-btn-success as-margin-right-space-1"
                                                    rounded="large"
                                                    size="large"
                                                    variant="outlined"
                                                    :disabled="!nyttSporsmal.tittel.trim()"
                                                    @click="leggTilSporsmal"
                                                >
                                                    Legg til
                                                </v-btn>
                                                <v-btn
                                                    class="v-btn-as v-btn-grey"
                                                    rounded="large"
                                                    size="large"
                                                    variant="outlined"
                                                    @click="avbrytNyttSporsmal"
                                                >
                                                    Avbryt
                                                </v-btn>
                                            </div>
                                        </div>
                                    </v-expand-transition>

                                    <!-- Spørsmål (flat liste, dra for rekkefølge) -->
                                    <template v-if="skjema.sporsmal.length">
                                        <div
                                            v-for="(s, index) in skjema.sporsmal"
                                            :key="sporsmalRowKey(s, index)"
                                            class="col-xs-12 sporsmal-item as-margin-bottom-space-2 nop-impt-fix"
                                            :class="{ 'sporsmal-item--source-drag': sporsmalDraggingIndex === index }"
                                            @dragover.prevent="sporsmalDragOverRow($event, index)"
                                            @drop.prevent="sporsmalDropOnRow(index)"
                                        >
                                            <div class="col-xs-12 nop-impt d-flex align-start">
                                                <div
                                                    class="sporsmal-drag-handle mr-2 mt-1"
                                                    title="Dra for å flytte"
                                                    draggable="true"
                                                    @dragstart="sporsmalDragStart($event, index)"
                                                    @dragend="sporsmalDragEnd"
                                                >
                                                    <div class="as-display-flex">
                                                        <h5 class="as-margin-auto-impt">{{ index + 1 }}</h5>
                                                        <v-icon color="grey">mdi-drag</v-icon>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 nop-impt">
                                                    <div class="col-xs-12 nop-impt d-flex flex-wrap">
                                                        <div class="col-xs-12 col-sm-3 nop-impt as-margin-right-space-2 mb-2 mb-sm-0">
                                                            <v-select
                                                                v-model="s.type"
                                                                :items="sporsmalTypeOptions"
                                                                item-title="label"
                                                                item-value="value"
                                                                label="Type"
                                                                variant="outlined"
                                                                class="v-autocomplete-arr-sys"
                                                                hide-details
                                                            />
                                                        </div>
                                                        <div class="col-xs-12 col-sm-6 nop-impt flex-grow-1 as-margin-right-space-2 mb-2 mb-sm-0">
                                                            <v-text-field
                                                                v-model="s.tittel"
                                                                label="Spørsmålstekst"
                                                                variant="outlined"
                                                                class="v-text-field-arr-sys"
                                                                density="compact"
                                                                hide-details
                                                            />
                                                        </div>
                                                        <div class="col-xs-12 col-sm-2 nop-impt sporsmal-delete-col">
                                                            <v-btn
                                                                class="v-btn-as v-btn-error"
                                                                icon
                                                                variant="text"
                                                                size="small"
                                                                @click="fjernSporsmal(s)"
                                                            >
                                                                <v-icon>mdi-delete-outline</v-icon>
                                                            </v-btn>
                                                        </div>
                                                    </div>
                                                    <div v-if="s.tekst !== undefined" class="col-xs-12 nop-impt as-margin-top-space-2">
                                                        <v-text-field
                                                            v-model="s.tekst"
                                                            label="Hjelpetekst"
                                                            variant="outlined"
                                                            class="v-text-field-arr-sys"
                                                            density="compact"
                                                            hide-details
                                                        />
                                                    </div>
                                                    <div v-if="s.id" class="col-xs-12 nop-impt as-margin-top-space-1 item-id-label">
                                                        ID #{{ s.id }} · Rekkefølge {{ s.rekkefolge }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div v-else-if="!visNyttSporsmalForm" class="col-xs-12 nop-impt as-text-center as-padding-space-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-comment-question-outline</v-icon>
                                        <p class="as-margin-top-space-2">Ingen spørsmål ennå</p>
                                        <p style="color: var(--color-primary-grey-dark);">Klikk «Legg til spørsmål» for å bygge opp skjemaet.</p>
                                    </div>

                                </div>
                            </v-tabs-window-item>

                        </v-tabs-window>

                        <!-- ── Buttons at bottom ──────────────────── -->
                        <div class="col-xs-12 nop-impt as-margin-top-space-3">
                            <v-btn
                                class="v-btn-as v-btn-success as-margin-right-space-2"
                                rounded="large"
                                size="large"
                                variant="outlined"
                                :loading="loading"
                                @click="$emit('lagre', skjema)"
                            >
                                Lagre skjemaet
                            </v-btn>

                            <v-btn
                                class="v-btn-as v-btn-error"
                                rounded="large"
                                size="large"
                                variant="outlined"
                                :loading="loading"
                                @click.stop="bekreftSlett = true"
                            >
                                Slett skjemaet
                            </v-btn>
                        </div>

                    </div>
                </template>

            </div>
        </v-expand-transition>

        <!-- ── Delete confirmation dialog ────────────────────────── -->
        <v-dialog v-model="bekreftSlett" max-width="420" persistent>
            <v-card rounded="lg">
                <v-card-title class="text-h6 pt-5 px-5">
                    <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
                    Slett spørreskjema
                </v-card-title>
                <v-card-text class="px-5">
                    Er du sikker på at du vil slette <strong>{{ skjema.navn || 'dette skjemaet' }}</strong>?
                    Alle tilknyttede spørsmål og svar vil også bli slettet. Handlingen kan ikke angres.
                </v-card-text>
                <v-card-actions class="px-5 pb-5">
                    <v-spacer />
                    <v-btn
                        class="v-btn-as v-btn-grey"
                        rounded="large"
                        variant="outlined"
                        @click="bekreftSlett = false"
                    >
                        Avbryt
                    </v-btn>
                    <v-btn
                        class="v-btn-as v-btn-error ml-2"
                        rounded="large"
                        variant="outlined"
                        color="error"
                        :loading="loading"
                        @click="$emit('slett', skjema); bekreftSlett = false"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Slett
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import type { SporreSkjema, SporsmalData } from '../objects/SporreSkjema';
import { slettSporsmal as apiSlettSporsmal } from '../services/sporreskjemaService';

export default {
    props: {
        skjema: {
            type: Object as PropType<SporreSkjema>,
            required: true,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['opprett', 'lagre', 'fjern', 'slett', 'feil'],

    beforeUnmount() {
        this.sporsmalRemoveDragGhost();
    },

    data() {
        return {
            bekreftSlett:       false,
            visNyttSporsmalForm: false,
            sporsmalLoading:    false,
            /** HTML5 DnD: kildeindeks i skjema.sporsmal, null når ikke aktiv */
            sporsmalDragFromIndex: null as number | null,
            /** Rad som visuelt «løftes» (klasse på kilden) */
            sporsmalDraggingIndex: null as number | null,
            nyttSporsmal: { type: 'kort_tekst', tittel: '', tekst: '' } as { type: string; tittel: string; tekst: string },

            sporsmalTypeOptions: [
                { value: 'kontakt',       label: 'Kontaktinformasjon (navn, epost, mobil)' },
                { value: 'janei',         label: 'Ja / nei' },
                { value: 'kort_tekst',    label: 'Kort tekst' },
                { value: 'lang_tekst',    label: 'Lang tekst' },
                { value: 'filopplasting', label: 'Filopplasting' },
                { value: 'intoleranser', label: 'Intoleranser / allergier' },
                { value: 'kontaktajourfore', label: 'Bekreftelse av brukerdata' },
            ],
        };
    },

    methods: {
        sporsmalRowKey(s: SporsmalData, index: number): string {
            return s.id > 0 ? `id-${s.id}` : `ny-${index}-${s.tittel}`;
        },

        sporsmalRemoveDragGhost(): void {
            const el = (this as unknown as { _sporsmalDragGhostEl: HTMLElement | null })._sporsmalDragGhostEl;
            if (el?.parentNode) {
                el.parentNode.removeChild(el);
            }
            (this as unknown as { _sporsmalDragGhostEl: HTMLElement | null })._sporsmalDragGhostEl = null;
        },

        sporsmalDragStart(e: DragEvent, index: number): void {
            this.sporsmalDragFromIndex = index;
            this.sporsmalDraggingIndex = index;

            const handle = e.currentTarget as HTMLElement | null;
            const row = handle?.closest('.sporsmal-item') as HTMLElement | null;
            const dt = e.dataTransfer;
            if (!dt) {
                return;
            }
            dt.effectAllowed = 'move';
            dt.setData('text/plain', String(index));

            if (!row) {
                return;
            }

            const rect = row.getBoundingClientRect();
            const clone = row.cloneNode(true) as HTMLElement;
            clone.classList.add('sporsmal-item--drag-ghost');
            clone.style.boxSizing = 'border-box';
            clone.style.width = `${rect.width}px`;
            clone.style.position = 'fixed';
            clone.style.left = '-10000px';
            clone.style.top = '0';
            clone.style.zIndex = '100000';
            clone.style.pointerEvents = 'none';
            clone.style.margin = '0';
            document.body.appendChild(clone);

            const offsetX = Math.round(e.clientX - rect.left);
            const offsetY = Math.round(e.clientY - rect.top);
            dt.setDragImage(clone, offsetX, offsetY);

            (this as unknown as { _sporsmalDragGhostEl: HTMLElement | null })._sporsmalDragGhostEl = clone;
        },

        sporsmalDragEnd(): void {
            this.sporsmalRemoveDragGhost();
            this.sporsmalDragFromIndex = null;
            this.sporsmalDraggingIndex = null;
        },

        sporsmalDragOverRow(e: DragEvent, _index: number): void {
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
        },

        sporsmalDropOnRow(dropIndex: number): void {
            const from = this.sporsmalDragFromIndex;
            if (from === null || from === dropIndex) {
                this.sporsmalDragEnd();
                return;
            }
            const arr = this.skjema.sporsmal;
            if (from < 0 || from >= arr.length || dropIndex < 0 || dropIndex > arr.length) {
                this.sporsmalDragEnd();
                return;
            }
            const [moved] = arr.splice(from, 1);
            let insertAt = dropIndex;
            if (from < dropIndex) {
                insertAt = dropIndex - 1;
            }
            arr.splice(insertAt, 0, moved);
            arr.forEach((row, i) => {
                row.rekkefolge = i + 1;
            });
            this.sporsmalDragEnd();
        },

        leggTilSporsmal(): void {
            if (!this.nyttSporsmal.tittel.trim()) return;
            const nextRekkefolge = this.skjema.sporsmal.length + 1;
            this.skjema.sporsmal.push({
                id:         0,
                skjema_id:  this.skjema.id,
                rekkefolge: nextRekkefolge,
                type:       this.nyttSporsmal.type,
                tittel:     this.nyttSporsmal.tittel.trim(),
                tekst:      this.nyttSporsmal.tekst.trim(),
            } as SporsmalData);
            this.avbrytNyttSporsmal();
        },

        avbrytNyttSporsmal(): void {
            this.nyttSporsmal = { type: 'kort_tekst', tittel: '', tekst: '' };
            this.visNyttSporsmalForm = false;
        },

        async fjernSporsmal(sporsmal: SporsmalData): Promise<void> {
            const idx = this.skjema.sporsmal.indexOf(sporsmal);
            if (idx === -1) return;

            // Persisted question — delete via API first
            if (sporsmal.id > 0) {
                this.sporsmalLoading = true;
                try {
                    await apiSlettSporsmal(this.skjema.id, sporsmal.id);
                } catch (e: any) {
                    this.$emit('feil', e.message ?? 'Kunne ikke slette spørsmål');
                    this.sporsmalLoading = false;
                    return;
                } finally {
                    this.sporsmalLoading = false;
                }
            }

            this.skjema.sporsmal.splice(idx, 1);
            this.skjema.sporsmal.forEach((s, i) => { s.rekkefolge = i + 1; });
        },
    },
};
</script>

<style scoped>
.skjema-item {
    padding: calc(3 * var(--initial-space-box)) !important;
    border: none;
    box-shadow: none !important;
    min-height: 56px !important;
    cursor: pointer;
    border-radius: var(--radius-high) !important;
}

.nop-impt-fix {
    padding: calc(3 * var(--initial-space-box)) !important;
}
.col-xs-inner-box {
    background: var(--color-primary-grey-lightest) !important;
    padding: 30px 20px !important;
    border-radius: var(--radius-normal) !important;
}

.tab-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sporsmal-drag-handle {
    cursor: grab;
    touch-action: none;
    user-select: none;
    flex-shrink: 0;
}
.sporsmal-drag-handle:active {
    cursor: grabbing;
}

.sporsmal-item {
    background: var(--color-primary-grey-lightest);
    border-radius: var(--radius-normal) !important;
    border: solid 1px var(--color-primary-grey-light);
    padding: calc(4 * var(--initial-space-box)) calc(2 * var(--initial-space-box)) !important;
}
/* Kilden mens man drar — tydelig «tom» plass */
.sporsmal-item--source-drag {
    opacity: 0.45;
    border-style: dashed;
}
/* Forhåndsvisning under pekeren (setDragImage) */
.sporsmal-item--drag-ghost {
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
    border: solid 1px var(--color-primary-grey-light) !important;
    background: var(--color-primary-grey-lightest) !important;
}
.sporsmal-item--indented {
    margin-left: calc(3 * var(--initial-space-box));
}
.sporsmal-delete-col {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.item-id-label {
    font-size: 12px;
    color: var(--color-primary-grey-dark);
}
.extended-skjema-content {
    overflow: hidden;
}
</style>
