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
            </v-tabs>
        </div>

        <div class="as-container">
            <div class="container skjema-container">

                <!-- Global error -->
                <v-alert
                    v-if="feil"
                    type="error"
                    variant="tonal"
                    closable
                    class="as-margin-top-space-3 as-margin-bottom-space-3"
                    @click:close="feil = ''"
                >{{ feil }}</v-alert>

                <div class="as-margin-top-space-4">
                    <v-tabs-window v-model="tab">

                        <v-tabs-window-item>
                            <SamtykkeskjemaTab @feil="feil = $event" />
                        </v-tabs-window-item>

                        <v-tabs-window-item>
                            <SporreskjemaTab @feil="feil = $event" />
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

const director = new Director();

export default {
    components: {
        SamtykkeskjemaTab,
        SporreskjemaTab,
    },

    data() {
        return {
            tab:  null as number | null,
            feil: '' as string,
        };
    },

    mounted() {
        const savedTab = director.getParam('tab');
        this.tab = savedTab !== null ? Number(savedTab) : 0;

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
</style>
