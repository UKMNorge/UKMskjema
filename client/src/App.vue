<template>
    <div class="as-margin-top-space-2">

        <!-- ── Top tab bar ───────────────────────────────────────────── -->
        <div class="container-fluid">
            <v-tabs
                v-model="tab"
                align-tabs="center"
                fixed-tabs
                bg-color="#fff"
                class="as-card-1 nosh-impt"
            >
                <v-tab text="Samtykkeskjemaer" />
                <v-tab text="Spørreskjemaer" />
                <v-tab text="Oppgaver" />
            </v-tabs>
        </div>

        <div v-if="feil" class="app-feil-topp container-fluid">
            <v-alert
                type="error"
                variant="tonal"
                closable
                class="mb-0"
                @click:close="feil = ''"
            >{{ feil }}</v-alert>
        </div>

        <div class="as-container">
            <div class="container skjema-container">

                <div class="as-margin-top-space-4">
                    <v-tabs-window v-model="tab">

                        <v-tabs-window-item>
                            <SamtykkeskjemaTab @feil="feil = $event" />
                        </v-tabs-window-item>

                        <v-tabs-window-item>
                            <SporreskjemaTab @feil="feil = $event" />
                        </v-tabs-window-item>

                        <v-tabs-window-item>
                            <OppgaveTab @feil="feil = $event" />
                        </v-tabs-window-item>

                    </v-tabs-window>
                </div>

            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { watch } from 'vue';
import { Director } from 'ukm-spa/Director';
import SamtykkeskjemaTab from './tabs/SamtykkeskjemaTab.vue';
import SporreskjemaTab from './tabs/SporreskjemaTab.vue';
import OppgaveTab from './tabs/OppgaveTab.vue';
import { OPPGAVE_TAB_INDEX, readRespondentSvarFromUrl } from './utils/oppgaveUrl';

const director = new Director();

export default {
    components: {
        SamtykkeskjemaTab,
        SporreskjemaTab,
        OppgaveTab,
    },

    data() {
        return {
            tab:  null as number | null,
            feil: '' as string,
        };
    },

    mounted() {
        const savedTab = director.getParam('tab');
        if (readRespondentSvarFromUrl()) {
            this.tab = OPPGAVE_TAB_INDEX;
            director.addParam('tab', String(OPPGAVE_TAB_INDEX));
        } else {
            this.tab = savedTab !== null ? Number(savedTab) : 0;
        }

        watch(() => this.tab, (newTab) => {
            director.addParam('tab', newTab);
        });
    },
};
</script>

<style scoped>
.skjema-container {
    padding: 0;
    max-width: 100%;
}
.app-feil-topp {
    position: sticky;
    top: 50px !important;
    z-index: 10;
    background: #fff;
    padding: .75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, .08);
    box-shadow: 0 4px 12px -8px #00000026;
    margin-top: 15px !important;
    margin-right: 15px;
    padding: 0;
}
</style>
