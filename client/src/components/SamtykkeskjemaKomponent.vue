<template>
    <div class="as-card-1 nop-impt as-margin-bottom-space-2">

        <!-- ── Header row (clickable) ─────────────────────────────── -->
        <v-list-item
            @click="skjema.expanded = !skjema.expanded"
            class="skjema-item nop-impt as-card-1 as-padding-space-3"
        >
            <div class="d-flex justify-space-between align-center">
                <v-list-item-title class="text-h6">
                    {{ skjema.navn || 'Nytt skjema' }}
                </v-list-item-title>
            </div>

            <template v-slot:prepend>
                <v-avatar color="grey-lighten-1">
                    <v-icon color="white">mdi-file-sign</v-icon>
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
                            <h5>Nytt samtykkeskjema</h5>
                        </div>
                        <div class="col-xs-6 nop-impt as-margin-right-space-2">
                            <v-text-field
                                v-model="skjema.navn"
                                label="Navn på skjema"
                                placeholder="F.eks. Samtykkeskjema 2026"
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
                            <v-tab value="prosjekter">
                                <v-icon start>mdi-folder-outline</v-icon>
                                Prosjekter
                                <v-chip v-if="skjema.prosjekter.length" size="x-small" class="ml-2" color="primary">
                                    {{ skjema.prosjekter.length }}
                                </v-chip>
                            </v-tab>
                            <v-tab value="versjon">
                                <v-icon start>mdi-tag-outline</v-icon>
                                Versjon
                                <v-chip v-if="skjema.versjon" size="x-small" class="ml-2" color="success">
                                    {{ skjema.versjon.versjon_nr }}
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
                                    <div class="col-xs-6 nop-impt">
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
                                        ID #{{ skjema.id }}
                                    </div>
                                </div>
                            </v-tabs-window-item>

                            <!-- ── TAB: Prosjekter ────────────── -->
                            <v-tabs-window-item value="prosjekter">
                                <div class="col-xs-12 nop-impt">

                                    <div class="col-xs-12 nop-impt as-margin-bottom-space-3 tab-section-header">
                                        <div class="tidspunkt-tittel">
                                            <h5>Prosjekter knyttet til skjemaet</h5>
                                        </div>
                                        <v-btn
                                            class="v-btn-as v-btn-bla"
                                            rounded="large"
                                            size="large"
                                            variant="outlined"
                                            @click="skjema.visNyProsjektForm = !skjema.visNyProsjektForm"
                                        >
                                            <v-icon start>mdi-plus</v-icon>
                                            Legg til prosjekt
                                        </v-btn>
                                    </div>

                                    <!-- Add-prosjekt form -->
                                    <v-expand-transition>
                                        <div v-if="skjema.visNyProsjektForm" class="col-xs-12 col-xs-inner-box as-margin-bottom-space-3 nop-impt-fix">
                                            <div class="tidspunkt-tittel as-margin-bottom-space-3">
                                                <h5>Nytt prosjekt</h5>
                                            </div>
                                            <div class="col-xs-12 nop-impt">
                                                <div class="col-xs-5 nop-impt as-margin-right-space-2">
                                                    <v-text-field
                                                        v-model="skjema.nyProsjekt.navn"
                                                        label="Prosjektnavn *"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details="auto"
                                                    />
                                                </div>
                                                <div class="col-xs-5 nop-impt">
                                                    <v-text-field
                                                        v-model.number="skjema.nyProsjekt.arrangement_id"
                                                        label="Arrangement ID (valgfritt)"
                                                        type="number"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details="auto"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-xs-12 nop-impt as-margin-top-space-2">
                                                <v-textarea
                                                    v-model="skjema.nyProsjekt.beskrivelse"
                                                    label="Beskrivelse (valgfritt)"
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
                                                    :disabled="!skjema.nyProsjekt.navn.trim()"
                                                    @click="leggTilProsjekt"
                                                >
                                                    Legg til
                                                </v-btn>
                                                <v-btn
                                                    class="v-btn-as v-btn-grey"
                                                    rounded="large"
                                                    size="large"
                                                    variant="outlined"
                                                    @click="avbrytNyProsjekt"
                                                >
                                                    Avbryt
                                                </v-btn>
                                            </div>
                                        </div>
                                    </v-expand-transition>

                                    <!-- Prosjekt list -->
                                    <div v-if="skjema.prosjekter.length" class="col-xs-12 nop-impt">
                                        <div
                                            v-for="(p, i) in skjema.prosjekter"
                                            :key="i"
                                            class="col-xs-12 prosjekt-item as-margin-bottom-space-2 nop-impt-fix"
                                        >
                                            <div class="col-xs-12 nop-impt">
                                                <div class="col-xs-5 nop-impt as-margin-right-space-2">
                                                    <v-text-field
                                                        v-model="p.navn"
                                                        label="Prosjektnavn"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details
                                                    />
                                                </div>
                                                <div class="col-xs-4 nop-impt as-margin-right-space-2">
                                                    <v-textarea
                                                        v-model="p.beskrivelse"
                                                        label="Beskrivelse"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        rows="1"
                                                        auto-grow
                                                        hide-details
                                                    />
                                                </div>
                                                <div class="col-xs-2 nop-impt">
                                                    <v-text-field
                                                        v-model.number="p.arrangement_id"
                                                        label="Arrangement ID"
                                                        type="number"
                                                        variant="outlined"
                                                        class="v-text-field-arr-sys"
                                                        density="compact"
                                                        hide-details
                                                    />
                                                </div>
                                                <div class="col-xs-1 nop-impt prosjekt-delete-col">
                                                    <v-btn
                                                        class="v-btn-as v-btn-error"
                                                        icon
                                                        variant="text"
                                                        size="small"
                                                        @click="skjema.prosjekter.splice(i, 1)"
                                                    >
                                                        <v-icon>mdi-delete-outline</v-icon>
                                                    </v-btn>
                                                </div>
                                            </div>
                                            <div v-if="p.id" class="col-xs-12 nop-impt as-margin-top-space-1 item-id-label">
                                                ID #{{ p.id }}
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else-if="!skjema.visNyProsjektForm" class="col-xs-12 nop-impt as-text-center as-padding-space-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-folder-open-outline</v-icon>
                                        <p class="as-margin-top-space-2">Ingen prosjekter ennå</p>
                                        <p style="color: var(--color-primary-grey-dark);">Klikk «Legg til prosjekt» for å koble et prosjekt til dette skjemaet.</p>
                                    </div>

                                </div>
                            </v-tabs-window-item>

                            <!-- ── TAB: Versjon ───────────────── -->
                            <v-tabs-window-item value="versjon">
                                <div class="col-xs-12 nop-impt">

                                    <div class="col-xs-12 nop-impt as-margin-bottom-space-3 tab-section-header">
                                        <div class="tidspunkt-tittel">
                                            <h5>Versjonsinformasjon</h5>
                                        </div>
                                        <v-btn
                                            v-if="!skjema.versjon"
                                            class="v-btn-as v-btn-bla"
                                            rounded="large"
                                            size="large"
                                            variant="outlined"
                                            @click="skjema.versjon = { versjon_nr: '1.0', beskrivelse: null, body_text: null, file_path: null }"
                                        >
                                            <v-icon start>mdi-plus</v-icon>
                                            Opprett versjon
                                        </v-btn>
                                    </div>

                                    <template v-if="skjema.versjon">
                                        <div class="col-xs-12 nop-impt">
                                            <div class="col-xs-4 nop-impt as-margin-right-space-2">
                                                <v-text-field
                                                    v-model="skjema.versjon.versjon_nr"
                                                    label="Versjonsnummer *"
                                                    placeholder="F.eks. 1.0"
                                                    variant="outlined"
                                                    class="v-text-field-arr-sys"
                                                    density="comfortable"
                                                    hide-details="auto"
                                                />
                                            </div>
                                            <div class="col-xs-7 nop-impt">
                                                <v-text-field
                                                    v-model="skjema.versjon.beskrivelse"
                                                    label="Beskrivelse"
                                                    variant="outlined"
                                                    class="v-text-field-arr-sys"
                                                    density="comfortable"
                                                    hide-details="auto"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-xs-12 col-xs-inner-box as-margin-top-space-3 nop-impt-fix">
                                            <div class="col-xs-12 nop-impt">
                                                <div class="tidspunkt-tittel as-margin-bottom-space-3">
                                                    <h5>Innhold / brødtekst</h5>
                                                </div>
                                                <v-textarea
                                                    v-model="skjema.versjon.body_text"
                                                    label="Innhold / brødtekst"
                                                    placeholder="Skriv inn teksten brukerne vil se når de skal gi samtykke…"
                                                    variant="outlined"
                                                    class="v-text-field-arr-sys"
                                                    density="comfortable"
                                                    rows="8"
                                                    hide-details="auto"
                                                />
                                            </div>
                                        </div>

                                        <div v-if="skjema.versjon.file_path" class="col-xs-10 nop-impt as-margin-top-space-3">
                                            <v-text-field
                                                v-model="skjema.versjon.file_path"
                                                label="Filsti (valgfritt)"
                                                variant="outlined"
                                                class="v-text-field-arr-sys"
                                                density="comfortable"
                                                hide-details="auto"
                                            />
                                        </div>

                                        <div v-if="skjema.versjon.id" class="col-xs-12 nop-impt as-margin-top-space-3 item-id-label">
                                            Versjon ID #{{ skjema.versjon.id }}
                                        </div>
                                    </template>

                                    <div v-else class="col-xs-12 nop-impt as-text-center as-padding-space-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-tag-off-outline</v-icon>
                                        <p class="as-margin-top-space-2">Ingen versjon opprettet</p>
                                        <p style="color: var(--color-primary-grey-dark);">Klikk «Opprett versjon» for å legge til versjonsinformasjon og brødtekst.</p>
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
                    Slett skjema
                </v-card-title>
                <v-card-text class="px-5">
                    Er du sikker på at du vil slette <strong>{{ skjema.navn || 'dette skjemaet' }}</strong>?
                    Alle tilknyttede versjoner og prosjekter vil også bli slettet. Handlingen kan ikke angres.
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
import type { SamtykkeSkjema } from '../objects/SamtykkeSkjema';

export default {
    props: {
        skjema: {
            type: Object as PropType<SamtykkeSkjema>,
            required: true,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['opprett', 'lagre', 'fjern', 'slett'],

    data() {
        return {
            bekreftSlett: false,
        };
    },

    methods: {
        leggTilProsjekt(): void {
            if (!this.skjema.nyProsjekt.navn.trim()) return;
            this.skjema.prosjekter.push({ ...this.skjema.nyProsjekt });
            this.avbrytNyProsjekt();
        },

        avbrytNyProsjekt(): void {
            this.skjema.nyProsjekt = { navn: '', beskrivelse: null, arrangement_id: null };
            this.skjema.visNyProsjektForm = false;
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

.prosjekt-item {
    background: var(--color-primary-grey-lightest);
    border-radius: var(--radius-normal) !important;
    border: solid 1px var(--color-primary-grey-light);
    padding: calc(2 * var(--initial-space-box)) !important;
}
.prosjekt-delete-col {
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
