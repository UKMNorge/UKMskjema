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
                @click="leggTilSporreskjema"
            >
                Legg til spørreskjema
            </v-btn>
        </div>

        <div class="as-padding-left-space-1 as-padding-right-space-1 as-margin-top-space-2 as-margin-bottom-space-2">
            <h4>Spørreskjemaer</h4>
            <div class="as-margin-top-space-2" v-if="hentet && alleSporreskjemaer.length < 1">
                <PermanentNotification 
                    typeNotification="info" 
                    :tittel="`Ingen spørreskjemaer ennå`" 
                    :description="`Ingen spørreskjemaer ennå. Klikk «Legg til spørreskjema» for å opprette det første.`" 
                />
            </div>
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
                <SporreskjemaKomponent
                    v-for="s in alleSporreskjemaer"
                    :key="s.id"
                    :skjema="s"
                    :loading="skjemaLoading"
                    @opprett="opprettSporreskjema"
                    @lagre="lagreSporreskjema"
                    @fjern="fjernNyttSporreskjema"
                    @slett="slettSporreskjema"
                    @feil="$emit('feil', $event)"
                />
            </v-list>
        </v-card>
    </div>
</template>

<script lang="ts">
import { SporreSkjema } from '../objects/SporreSkjema';
import { PermanentNotification } from 'ukm-components-vue3';
import {
    hentAlleSporreskjemaer as apiHentAlle,
    opprettSporreskjema as apiOpprett,
    lagreSporreskjema as apiLagre,
    slettSporreskjema as apiSlett,
} from '../services/sporreskjemaService';
import SporreskjemaKomponent from '../components/SporreskjemaKomponent.vue';

export default {
    components: { 
        SporreskjemaKomponent,
        PermanentNotification,
    },

    emits: ['feil'],

    data() {
        return {
            alleSporreskjemaer: [] as SporreSkjema[],
            hentet:             false as boolean,
            listeLoading:       false as boolean,
            skjemaLoading:      false as boolean,
        };
    },

    mounted() {
        this.hentAlle();
    },

    methods: {
        async hentAlle(): Promise<void> {
            this.listeLoading = true;
            try {
                const data = await apiHentAlle();
                this.alleSporreskjemaer = data.map((d: any) => new SporreSkjema(d));
                this.hentet = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av spørreskjemaer');
            } finally {
                this.listeLoading = false;
            }
        },

        leggTilSporreskjema(): void {
            for (const s of this.alleSporreskjemaer) {
                if (s.id === -1) { s.expanded = true; return; }
            }
            const nytt = new SporreSkjema({ id: -1 } as any);
            nytt.expanded = true;
            this.alleSporreskjemaer.unshift(nytt);
        },

        fjernNyttSporreskjema(): void {
            const idx = this.alleSporreskjemaer.findIndex(s => s.id === -1);
            if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1);
        },

        async opprettSporreskjema(skjema: SporreSkjema): Promise<void> {
            this.skjemaLoading = true;
            try {
                const data = await apiOpprett(skjema.type);
                this.fjernNyttSporreskjema();
                await this.hentAlle();
                const opprettet = this.alleSporreskjemaer.find(s => s.id === data.id);
                if (opprettet) opprettet.expanded = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved oppretting av spørreskjema');
            } finally {
                this.skjemaLoading = false;
            }
        },

        async lagreSporreskjema(skjema: SporreSkjema): Promise<void> {
            if (!skjema.id) {
                this.$emit('feil', 'Opprett et skjema før du lagrer data.');
                return;
            }
            this.skjemaLoading = true;
            try {
                const data = await apiLagre(skjema.id, skjema.sporsmal);
                const oppdatert = new SporreSkjema(data);
                oppdatert.expanded  = true;
                oppdatert.activeTab = skjema.activeTab;
                const idx = this.alleSporreskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1, oppdatert);
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved lagring av spørreskjema');
            } finally {
                this.skjemaLoading = false;
            }
        },

        async slettSporreskjema(skjema: SporreSkjema): Promise<void> {
            this.skjemaLoading = true;
            try {
                await apiSlett(skjema.id);
                const idx = this.alleSporreskjemaer.findIndex(s => s.id === skjema.id);
                if (idx !== -1) this.alleSporreskjemaer.splice(idx, 1);
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved sletting av spørreskjema');
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
