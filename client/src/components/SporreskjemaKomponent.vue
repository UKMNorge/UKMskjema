<template>
    <div class="as-card-1 nop-impt as-margin-bottom-space-2">

        <!-- ── Header row (clickable) ─────────────────────────────── -->
        <v-list-item
            @click="skjema.expanded = !skjema.expanded"
            class="skjema-item nop-impt as-card-1 as-padding-space-3"
        >
            <div class="d-flex justify-space-between align-center">
                <v-list-item-title class="text-h6">
                    {{ skjema.navn || 'Nytt spørreskjema' }}
                </v-list-item-title>
                <v-chip
                    v-if="skjema.id !== -1"
                    size="small"
                    :color="skjema.type === 'arrangement' ? 'indigo' : 'teal'"
                    variant="tonal"
                    class="ml-2"
                >
                    {{ skjema.type === 'arrangement' ? 'Arrangement' : 'Deltaker' }}
                </v-chip>
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
                        <div class="col-xs-6 nop-impt as-margin-right-space-2">
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
                        <div class="col-xs-4 nop-impt">
                            <v-select
                                v-model="skjema.type"
                                :items="typeOptions"
                                item-title="label"
                                item-value="value"
                                label="Type"
                                variant="outlined"
                                class="v-text-field-arr-sys"
                                density="comfortable"
                                hide-details="auto"
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
                            <v-tab value="respondenter">
                                <v-icon start>mdi-account-group-outline</v-icon>
                                Respondenter
                                <v-chip v-if="skjema.respondenter.length" size="x-small" class="ml-2" color="secondary">
                                    {{ skjema.respondenter.length }}
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
                                    <div class="col-xs-6 nop-impt as-margin-right-space-2">
                                        <v-text-field
                                            v-model="skjema.navn"
                                            label="Navn på skjema"
                                            variant="outlined"
                                            class="v-text-field-arr-sys"
                                            density="comfortable"
                                            hide-details="auto"
                                        />
                                    </div>
                                    <div class="col-xs-3 nop-impt">
                                        <v-select
                                            v-model="skjema.type"
                                            :items="typeOptions"
                                            item-title="label"
                                            item-value="value"
                                            label="Type"
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
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
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

                                    <!-- Question list grouped by overskrift -->
                                    <template v-if="skjema.sporsmal.length">
                                        <div
                                            v-for="(gruppe, gi) in skjema.getSporsmalPerOverskrift()"
                                            :key="gi"
                                            class="col-xs-12 nop-impt as-margin-bottom-space-3"
                                        >
                                            <!-- Overskrift row -->
                                            <div v-if="gruppe.overskrift" class="sporsmal-overskrift-row as-margin-bottom-space-2">
                                                <div class="col-xs-10 nop-impt">
                                                    <v-text-field
                                                        v-model="gruppe.overskrift.tittel"
                                                        label="Overskrift"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details
                                                        prepend-inner-icon="mdi-format-header-2"
                                                    />
                                                </div>
                                                <div class="col-xs-1 nop-impt sporsmal-delete-col">
                                                    <v-btn
                                                        class="v-btn-as v-btn-error"
                                                        icon
                                                        variant="text"
                                                        size="small"
                                                        @click="fjernSporsmal(gruppe.overskrift)"
                                                    >
                                                        <v-icon>mdi-delete-outline</v-icon>
                                                    </v-btn>
                                                </div>
                                            </div>

                                            <!-- Questions under this overskrift -->
                                            <div
                                                v-for="(s, si) in gruppe.sporsmal"
                                                :key="si"
                                                class="col-xs-12 sporsmal-item as-margin-bottom-space-2 nop-impt-fix"
                                                :class="{ 'sporsmal-item--indented': gruppe.overskrift }"
                                            >
                                                <div class="col-xs-12 nop-impt">
                                                    <div class="col-xs-2 nop-impt as-margin-right-space-2">
                                                        <v-select
                                                            v-model="s.type"
                                                            :items="sporsmalTypeOptions"
                                                            item-title="label"
                                                            item-value="value"
                                                            label="Type"
                                                            variant="outlined"
                                                            class="v-text-field-arr-sys"
                                                            density="compact"
                                                            hide-details
                                                        />
                                                    </div>
                                                    <div class="col-xs-6 nop-impt as-margin-right-space-2">
                                                        <v-text-field
                                                            v-model="s.tittel"
                                                            label="Spørsmålstekst"
                                                            variant="outlined"
                                                            class="v-text-field-arr-sys"
                                                            density="compact"
                                                            hide-details
                                                        />
                                                    </div>
                                                    <div class="col-xs-1 nop-impt sporsmal-delete-col">
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
                                                <div v-if="s.tekst !== undefined" class="col-xs-10 nop-impt as-margin-top-space-1">
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
                                    </template>

                                    <div v-else-if="!visNyttSporsmalForm" class="col-xs-12 nop-impt as-text-center as-padding-space-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-comment-question-outline</v-icon>
                                        <p class="as-margin-top-space-2">Ingen spørsmål ennå</p>
                                        <p style="color: var(--color-primary-grey-dark);">Klikk «Legg til spørsmål» for å bygge opp skjemaet.</p>
                                    </div>

                                </div>
                            </v-tabs-window-item>

                            <!-- ── TAB: Respondenter ──────────── -->
                            <v-tabs-window-item value="respondenter">
                                <div class="col-xs-12 nop-impt">
                                    <div class="tidspunkt-tittel as-margin-bottom-space-3">
                                        <h5>Respondenter</h5>
                                    </div>

                                    <template v-if="skjema.respondenter.length">
                                        <div
                                            v-for="(r, ri) in skjema.respondenter"
                                            :key="ri"
                                            class="col-xs-12 respondent-item as-margin-bottom-space-2 nop-impt-fix"
                                        >
                                            <div class="d-flex align-center justify-space-between">
                                                <div class="d-flex align-center">
                                                    <v-icon class="mr-3" color="grey">
                                                        {{ r.type === 'arrangement' ? 'mdi-calendar-star' : 'mdi-account' }}
                                                    </v-icon>
                                                    <div>
                                                        <div class="respondent-navn">{{ r.navn || ('ID #' + r.id) }}</div>
                                                        <div class="item-id-label">{{ r.type === 'arrangement' ? 'Arrangement' : 'Person' }} · ID #{{ r.id }}</div>
                                                    </div>
                                                </div>
                                                <v-chip
                                                    size="small"
                                                    :color="r.svar ? 'success' : 'grey'"
                                                    variant="tonal"
                                                >
                                                    {{ r.svar ? 'Besvart' : 'Ikke besvart' }}
                                                </v-chip>
                                            </div>
                                        </div>
                                    </template>

                                    <div v-else class="col-xs-12 nop-impt as-text-center as-padding-space-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-account-group-outline</v-icon>
                                        <p class="as-margin-top-space-2">Ingen respondenter ennå</p>
                                        <p style="color: var(--color-primary-grey-dark);">Ingen har besvart dette skjemaet.</p>
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
import { SporreSkjema } from '../objects/SporreSkjema';
import type { SporsmalData } from '../objects/SporreSkjema';
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

    data() {
        return {
            bekreftSlett:       false,
            visNyttSporsmalForm: false,
            sporsmalLoading:    false,
            nyttSporsmal: { type: 'tekst', tittel: '', tekst: '' } as { type: string; tittel: string; tekst: string },

            typeOptions: [
                { value: 'arrangement', label: 'Arrangement' },
                { value: 'person',      label: 'Deltaker (person)' },
            ],

            sporsmalTypeOptions: [
                { value: 'tekst',      label: 'Kort tekst' },
                { value: 'textarea',   label: 'Lang tekst' },
                { value: 'ja_nei',     label: 'Ja / Nei' },
                { value: 'flervalg',   label: 'Flervalg' },
                { value: 'overskrift', label: 'Overskrift' },
            ],
        };
    },

    methods: {
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
            this.nyttSporsmal = { type: 'tekst', tittel: '', tekst: '' };
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

.sporsmal-overskrift-row {
    display: flex;
    align-items: center;
    border-bottom: 2px solid var(--color-primary-grey-light);
    padding-bottom: calc(1 * var(--initial-space-box));
}

.sporsmal-item {
    background: var(--color-primary-grey-lightest);
    border-radius: var(--radius-normal) !important;
    border: solid 1px var(--color-primary-grey-light);
    padding: calc(2 * var(--initial-space-box)) !important;
}
.sporsmal-item--indented {
    margin-left: calc(3 * var(--initial-space-box));
}
.sporsmal-delete-col {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.respondent-item {
    background: var(--color-primary-grey-lightest);
    border-radius: var(--radius-normal) !important;
    border: solid 1px var(--color-primary-grey-light);
    padding: calc(2 * var(--initial-space-box)) !important;
}
.respondent-navn {
    font-weight: 500;
    font-size: 14px;
}

.item-id-label {
    font-size: 12px;
    color: var(--color-primary-grey-dark);
}
.extended-skjema-content {
    overflow: hidden;
}
</style>
