<template>
    <div>
        <!-- Section header -->
        <div class="section-header as-margin-bottom-space-5">
            <v-btn
                class="v-btn-as v-btn-hvit"
                prepend-icon="mdi-plus"
                color="#000"
                rounded="large"
                variant="outlined"
                size="x-large"
                @click="leggTilSamtykkeskjema"
            >
                Legg til samtykkeskjema
            </v-btn>
        </div>

        <div class="as-padding-left-space-1 as-padding-right-space-1 as-margin-top-space-2 as-margin-bottom-space-2">
            <h4>Samtykkeskjemaer</h4>
        </div>

        <!-- Loading skeleton -->
        <template v-if="listeLoading">
            <v-skeleton-loader
                v-for="n in 3"
                :key="n"
                type="list-item-avatar"
                class="as-margin-bottom-space-2 skjema-skeleton"
            />
        </template>

        <!-- List -->
        <v-card class="mx-auto skjema-card">
            <v-list lines="three" class="skjema-list">
                <SamtykkeskjemaKomponent
                    v-for="s in alleSamtykkeskjemaer"
                    :key="s.id"
                    :skjema="s"
                    :loading="skjemaLoading"
                    @opprett="opprettSamtykkeskjema"
                    @lagre="lagreSamtykkeskjema"
                    @fjern="fjernNyttSamtykkeskjema"
                    @slett="slettSamtykkeskjema"
                />
            </v-list>
        </v-card>
    </div>
</template>

<script lang="ts">
import { SamtykkeSkjema } from '../objects/SamtykkeSkjema';
import { PermanentNotification } from 'ukm-components-vue3';
import {
    hentAlleSamtykkeskjemaer as apiHentAlle,
    opprettSamtykkeskjema as apiOpprett,
    lagreAllDataSamtykkeskjema as apiLagreAllData,
    slettSamtykkeskjema as apiSlett,
} from '../services/skjemaService';
import SamtykkeskjemaKomponent from '../components/SamtykkeskjemaKomponent.vue';

export default {
    components: { 
        SamtykkeskjemaKomponent,
        PermanentNotification,
    },

    emits: ['feil'],

    data() {
        return {
            alleSamtykkeskjemaer: [] as SamtykkeSkjema[],
            arrangementType:      '' as string,
            hentet:               false as boolean,
            listeLoading:         false as boolean,
            skjemaLoading:        false as boolean,
        };
    },

    computed: {
        
    },

    mounted() {
        this.hentAlle();
    },

    methods: {
        async hentAlle(): Promise<void> {
            this.listeLoading = true;
            try {
                const data = await apiHentAlle();
                this.alleSamtykkeskjemaer = data.skjemaer.map((d: any) => new SamtykkeSkjema(d));
                this.arrangementType = data.arrangement_type;
                this.hentet = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av samtykkeskjemaer');
            } finally {
                this.listeLoading = false;
            }
        },

        leggTilSamtykkeskjema(): void {
            for (const s of this.alleSamtykkeskjemaer) {
                if (s.id === -1) { s.expanded = true; return; }
            }
            const nytt = new SamtykkeSkjema({ id: -1, navn: '', type: 'vanlig', subtype: 'standard' } as any);
            nytt.expanded = true;
            this.alleSamtykkeskjemaer.unshift(nytt);
        },

        fjernNyttSamtykkeskjema(): void {
            const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === -1);
            if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1);
        },

        async opprettSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            this.skjemaLoading = true;
            try {
                const data = await apiOpprett(skjema.navn, skjema.type, skjema.subtype);
                this.fjernNyttSamtykkeskjema();
                await this.hentAlle();
                const opprettet = this.alleSamtykkeskjemaer.find(s => s.id === data.id);
                if (opprettet) opprettet.expanded = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved oppretting av samtykkeskjema');
            } finally {
                this.skjemaLoading = false;
            }
        },

        async lagreSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            if (!skjema.id) {
                this.$emit('feil', 'Opprett et skjema før du lagrer data.');
                return;
            }
            this.skjemaLoading = true;
            try {
                const data = await apiLagreAllData(
                    skjema.id,
                    skjema.navn,
                    skjema.type,
                    skjema.subtype,
                    skjema.prosjekter,
                    skjema.versjon
                );
                const oppdatert = new SamtykkeSkjema(data);
                oppdatert.expanded  = true;
                oppdatert.activeTab = skjema.activeTab;
                const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1, oppdatert);
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved lagring av samtykkeskjema');
            } finally {
                this.skjemaLoading = false;
            }
        },

        async slettSamtykkeskjema(skjema: SamtykkeSkjema): Promise<void> {
            this.skjemaLoading = true;
            try {
                await apiSlett(skjema.id);
                const idx = this.alleSamtykkeskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSamtykkeskjemaer.splice(idx, 1);
            } catch (e: any) {
                this.$emit('feil', e.responseText ? JSON.parse(e.responseText).result : 'Feil ved sletting av samtykkeskjema');
            } finally {
                this.skjemaLoading = false;
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
