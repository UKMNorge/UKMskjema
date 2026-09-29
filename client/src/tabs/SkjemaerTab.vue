<template>
    <div>
        <div class="section-header as-margin-bottom-space-5">
            <v-menu>
                <template #activator="{ props }">
                    <v-btn
                        v-bind="props"
                        class="v-btn-as v-btn-hvit"
                        prepend-icon="mdi-plus"
                        append-icon="mdi-chevron-down"
                        color="#000"
                        rounded="large"
                        variant="outlined"
                        size="x-large"
                    >
                        Legg til
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item title="Samtykke" @click="leggTilSamtykkeskjema" />
                    <v-list-item title="Spørreskjema" @click="leggTilSporreskjema" />
                </v-list>
            </v-menu>
        </div>

        <template v-if="listeLoading">
            <v-skeleton-loader
                v-for="n in 3"
                :key="n"
                type="list-item-avatar"
                class="as-margin-bottom-space-2 skjema-skeleton"
            />
        </template>

        <template v-else-if="!skjemaListe.length">
            <div class="skjema-filter as-margin-bottom-space-2">
                <v-chip
                    v-for="valg in typeFilterValg"
                    :key="valg.value"
                    class="skjema-filter__chip"
                    size="small"
                    :variant="typeFilter === valg.value ? 'flat' : 'outlined'"
                    :color="valg.color"
                    role="button"
                    tabindex="0"
                    @click="typeFilter = valg.value"
                    @keyup.enter="typeFilter = valg.value"
                >
                    {{ valg.label }}: {{ valg.antall }}
                </v-chip>
            </div>
            <p class="tom-liste-tekst as-margin-top-space-2">
                Ingen skjemaer av denne typen.
            </p>
        </template>

        <v-card v-else class="mx-auto skjema-card">
            
            <v-list lines="three" class="skjema-list">
                <div class="skjema-filter as-margin-bottom-space-2">
                    <v-chip
                        v-for="valg in typeFilterValg"
                        :key="valg.value"
                        class="skjema-filter__chip"
                        size="small"
                        :variant="typeFilter === valg.value ? 'flat' : 'outlined'"
                        :color="valg.color"
                        role="button"
                        tabindex="0"
                        @click="typeFilter = valg.value"
                        @keyup.enter="typeFilter = valg.value"
                    >
                        {{ valg.label }}: {{ valg.antall }}
                    </v-chip>
                </div>
                <template v-for="rad in skjemaListe" :key="rad.key">
                    <SamtykkeskjemaKomponent
                        v-if="rad.kind === 'samtykke'"
                        :skjema="rad.skjema"
                        :loading="samtykkeLoading"
                        @opprett="opprettSamtykkeskjema"
                        @lagre="lagreSamtykkeskjema"
                        @fjern="fjernNyttSamtykkeskjema"
                        @slett="slettSamtykkeskjema"
                    />
                    <SporreskjemaKomponent
                        v-else-if="rad.kind === 'sporreskjema'"
                        :skjema="rad.skjema"
                        :loading="sporreLoading"
                        @opprett="opprettSporreskjema"
                        @lagre="lagreSporreskjema"
                        @lagre-kun-sporsmal="lagreKunSporsmal(rad.skjema)"
                        @fjern="fjernNyttSporreskjema"
                        @slett="slettSporreskjema"
                        @feil="$emit('feil', $event)"
                    />
                </template>
            </v-list>
        </v-card>
    </div>
</template>

<script lang="ts">
import { SamtykkeSkjema } from '../objects/SamtykkeSkjema';
import { SporreSkjema } from '../objects/SporreSkjema';
import {
    hentAlleSamtykkeskjemaer as apiHentSamtykker,
    opprettSamtykkeskjema as apiOpprettSamtykke,
    lagreAllDataSamtykkeskjema as apiLagreSamtykke,
    slettSamtykkeskjema as apiSlettSamtykke,
} from '../services/skjemaService';
import {
    hentAlleSporreskjemaer as apiHentSporreskjemaer,
    opprettSporreskjema as apiOpprettSporre,
    lagreSporreskjema as apiLagreSporre,
    slettSporreskjema as apiSlettSporre,
} from '../services/sporreskjemaService';
import SamtykkeskjemaKomponent from '../components/SamtykkeskjemaKomponent.vue';
import SporreskjemaKomponent from '../components/SporreskjemaKomponent.vue';

type SkjemaKind = 'samtykke' | 'sporreskjema';
type SkjemaTypeFilter = 'alle' | SkjemaKind;

type SkjemaRad =
    | { kind: 'samtykke'; key: string; skjema: SamtykkeSkjema }
    | { kind: 'sporreskjema'; key: string; skjema: SporreSkjema };

export default {
    components: {
        SamtykkeskjemaKomponent,
        SporreskjemaKomponent,
    },

    emits: ['feil'],

    data() {
        return {
            alleSamtykkeskjemaer: [] as SamtykkeSkjema[],
            alleSporreskjemaer:   [] as SporreSkjema[],
            samtykkeHentet:       false as boolean,
            listeLoading:         false as boolean,
            samtykkeLoading:      false as boolean,
            sporreLoading:        false as boolean,
            utkastTeller:         0 as number,
            utkastStamp:          {} as Record<string, number>,
            typeFilter:           'alle' as SkjemaTypeFilter,
        };
    },

    computed: {
        antallSamtykker(): number {
            return this.alleSamtykkeskjemaer.filter((s) => s.id !== -1).length;
        },

        antallSporreskjemaer(): number {
            return this.alleSporreskjemaer.filter((s) => s.id !== -1).length;
        },

        typeFilterValg(): { value: SkjemaTypeFilter; label: string; color: string; antall: number }[] {
            return [
                { value: 'alle', label: 'Alle', color: 'primary', antall: this.antallSamtykker + this.antallSporreskjemaer },
                { value: 'samtykke', label: 'Samtykke', color: 'primary', antall: this.antallSamtykker },
                { value: 'sporreskjema', label: 'Spørreskjema', color: 'secondary', antall: this.antallSporreskjemaer },
            ];
        },

        skjemaListe(): SkjemaRad[] {
            const rader: SkjemaRad[] = [
                ...this.alleSamtykkeskjemaer.map((skjema) => ({
                    kind: 'samtykke' as const,
                    key: `samtykke-${skjema.id}`,
                    skjema,
                })),
                ...this.alleSporreskjemaer.map((skjema) => ({
                    kind: 'sporreskjema' as const,
                    key: `sporreskjema-${skjema.id}`,
                    skjema,
                })),
            ];
            const utkast = rader
                .filter((rad) => rad.skjema.id === -1)
                .sort((a, b) => (this.utkastStamp[b.key] ?? 0) - (this.utkastStamp[a.key] ?? 0));
            const lagret = rader
                .filter((rad) => rad.skjema.id !== -1)
                .sort((a, b) => a.skjema.navn.localeCompare(b.skjema.navn, 'nb'));
            const alle = [...utkast, ...lagret];
            if (this.typeFilter === 'alle') {
                return alle;
            }
            return alle.filter((rad) => rad.kind === this.typeFilter);
        },
    },

    mounted() {
        this.hentAlle();
    },

    methods: {
        async hentAlle(): Promise<void> {
            this.listeLoading = true;
            await Promise.all([this.hentSamtykker(), this.hentSporreskjemaer()]);
            this.listeLoading = false;
        },

        async hentSamtykker(): Promise<void> {
            try {
                const data = await apiHentSamtykker();
                this.alleSamtykkeskjemaer = data.skjemaer.map((d: any) => new SamtykkeSkjema(d));
                this.samtykkeHentet = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av samtykkeskjemaer');
            }
        },

        async hentSporreskjemaer(): Promise<void> {
            try {
                const data = await apiHentSporreskjemaer();
                this.alleSporreskjemaer = data.skjemaer.map((d: any) => new SporreSkjema(d));
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av spørreskjemaer');
            }
        },

        leggTilSamtykkeskjema(): void {
            if (this.typeFilter === 'sporreskjema') {
                this.typeFilter = 'samtykke';
            }
            for (const s of this.alleSamtykkeskjemaer) {
                if (s.id === -1) { s.expanded = true; return; }
            }
            const nytt = new SamtykkeSkjema({ id: -1, navn: '', type: 'vanlig', subtype: 'standard' } as any);
            nytt.expanded = true;
            this.utkastTeller += 1;
            this.utkastStamp['samtykke--1'] = this.utkastTeller;
            this.alleSamtykkeskjemaer.unshift(nytt);
        },

        fjernNyttSamtykkeskjema(): void {
            const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === -1);
            if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1);
        },

        async opprettSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            this.samtykkeLoading = true;
            try {
                const data = await apiOpprettSamtykke(skjema.navn, skjema.type, skjema.subtype, skjema.parent_consent_requirement);
                this.fjernNyttSamtykkeskjema();
                await this.hentSamtykker();
                if (this.samtykkeHentet) {
                    const opprettet = this.alleSamtykkeskjemaer.find(s => s.id == data.id);
                    if (opprettet) {
                        opprettet.expanded = true;
                        opprettet.activeTab = 'versjon';
                        opprettet.versjon = {
                            versjon_nr: '1.0',
                            beskrivelse: null,
                            body_text: null,
                            file_path: null,
                        };
                    }
                }
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved oppretting av samtykkeskjema');
            } finally {
                this.samtykkeLoading = false;
            }
        },

        async lagreSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            if (!skjema.id) {
                this.$emit('feil', 'Opprett et skjema før du lagrer data.');
                return;
            }
            this.samtykkeLoading = true;
            try {
                const data = await apiLagreSamtykke(
                    skjema.id,
                    skjema.navn,
                    skjema.type,
                    skjema.subtype,
                    skjema.prosjekter,
                    skjema.versjon,
                    skjema.parent_consent_requirement
                );
                const oppdatert = new SamtykkeSkjema(data);
                oppdatert.expanded  = true;
                oppdatert.activeTab = skjema.activeTab;
                const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1, oppdatert);
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved lagring av samtykkeskjema');
            } finally {
                this.samtykkeLoading = false;
            }
        },

        async slettSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            this.samtykkeLoading = true;
            try {
                await apiSlettSamtykke(skjema.id);
                const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1);
            } catch (e: any) {
                this.$emit('feil', e.responseText ? JSON.parse(e.responseText).result : 'Feil ved sletting av samtykkeskjema');
            } finally {
                this.samtykkeLoading = false;
            }
        },

        leggTilSporreskjema(): void {
            if (this.typeFilter === 'samtykke') {
                this.typeFilter = 'sporreskjema';
            }
            for (const s of this.alleSporreskjemaer) {
                if (s.id === -1) { s.expanded = true; return; }
            }
            const nytt = new SporreSkjema({ id: -1 } as any);
            nytt.expanded = true;
            this.utkastTeller += 1;
            this.utkastStamp['sporreskjema--1'] = this.utkastTeller;
            this.alleSporreskjemaer.unshift(nytt);
        },

        fjernNyttSporreskjema(): void {
            const idx = this.alleSporreskjemaer.findIndex(s => s.id === -1);
            if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1);
        },

        async opprettSporreskjema(skjema: SporreSkjema): Promise<void> {
            this.sporreLoading = true;
            try {
                const data = await apiOpprettSporre(skjema.navn);
                this.fjernNyttSporreskjema();
                await this.hentSporreskjemaer();
                const opprettet = this.alleSporreskjemaer.find(s => s.id === data.id);
                if (opprettet) opprettet.expanded = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved oppretting av spørreskjema');
            } finally {
                this.sporreLoading = false;
            }
        },

        lagreKunSporsmal(skjema: SporreSkjema): Promise<void> {
            return this.lagreSporreskjema(skjema, true);
        },

        async lagreSporreskjema(skjema: SporreSkjema, kunSporsmal: boolean = false): Promise<void> {
            if (!skjema.id) {
                this.$emit('feil', 'Opprett et skjema før du lagrer data.');
                return;
            }
            this.sporreLoading = true;
            try {
                const data = await apiLagreSporre(
                    skjema.id,
                    skjema.sporsmal,
                    kunSporsmal ? undefined : skjema.navn,
                    kunSporsmal ? undefined : skjema.parent_consent_requirement
                );
                const oppdatert = new SporreSkjema(data);
                oppdatert.expanded  = true;
                oppdatert.activeTab = skjema.activeTab;
                const idx = this.alleSporreskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1, oppdatert);
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved lagring av spørreskjema');
            } finally {
                this.sporreLoading = false;
            }
        },

        async slettSporreskjema(skjema: SporreSkjema): Promise<void> {
            this.sporreLoading = true;
            try {
                await apiSlettSporre(skjema.id);
                const idx = this.alleSporreskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1);
            } catch (e: any) {
                this.$emit('feil', e.responseText ? JSON.parse(e.responseText).result : 'Feil ved sletting av spørreskjema');
            } finally {
                this.sporreLoading = false;
            }
        },
    },
};
</script>

<style scoped>
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.skjema-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.skjema-filter__chip {
    cursor: pointer;
}
.tom-liste-tekst {
    color: var(--color-primary-grey-dark);
    margin-top: calc(2 * var(--initial-space-box));
}
.skjema-list,
.skjema-card {
    background: transparent;
    box-shadow: none !important;
}
.skjema-list {
    padding: var(--initial-space-box) !important;
}
.skjema-skeleton {
    border-radius: var(--radius-high) !important;
}
</style>
