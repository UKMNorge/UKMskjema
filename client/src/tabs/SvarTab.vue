<template>
    <div>
        <OppgaveSvar
            v-if="respondentFraUrl"
            :oppgave-id="respondentFraUrl.oppgaveId"
            :arrangementId="plId"
            :phone="respondentFraUrl.phone"
            @feil="$emit('feil', $event)"
            @tilbake="synkRespondentFraUrl"
        />

        <template v-else>
            <template v-if="listeLoading">
                <v-skeleton-loader
                    v-for="n in 2"
                    :key="n"
                    type="card"
                    class="as-margin-bottom-space-2 skjema-skeleton"
                />
            </template>

            <div
                v-else-if="hentet && oppgaver.length < 1"
                class="as-padding-left-space-1 as-padding-right-space-1"
            >
                <PermanentNotification
                    typeNotification="info"
                    :tittel="'Ingen svar å følge opp'"
                    :isHTML="true"
                    :description="'<p>Svar fra respondenter vises her når en oppgave er opprettet.</p>'"
                />
            </div>

            <div
                v-for="o in oppgaver"
                :key="o.id"
                class="as-margin-bottom-space-4 as-card-1 as-padding-space-3"
            >
                <div class="d-flex align-center flex-wrap gap-1 as-margin-bottom-space-1">
                    <h4>{{ o.name }}</h4>
                    <v-chip
                        v-if="o.locked"
                        size="small"
                        color="success"
                        class="as-margin-left-space-1"
                    >
                        Publisert
                    </v-chip>
                    <v-chip
                        v-else
                        size="small"
                        color="warning"
                        class="as-margin-left-space-1"
                    >
                        Ikke publisert
                    </v-chip>
                </div>
                <v-chip
                    v-if="o.arrangement_navn"
                    size="small"
                    variant="tonal"
                    color="primary"
                    class="as-margin-top-space-1 as-margin-bottom-space-1"
                    prepend-icon="mdi-calendar-outline"
                >
                    {{ o.arrangement_navn }}
                </v-chip>
                <div v-if="o.type || o.description">
                    <span v-if="o.type">{{ typeLabel(o.type) }}</span>
                    <span v-if="o.type && o.description"> · </span>
                    <span v-if="o.description">{{ o.description }}</span>
                </div>

                <OppgaveDeltakereKomponent
                    :oppgave-id="o.id"
                    :oppgave-type="o.type"
                    :arrangement-id="plId"
                    :kan-importere="erLandArrangement"
                    :locked="o.locked"
                    @feil="$emit('feil', $event)"
                />
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import { PermanentNotification } from 'ukm-components-vue3';
import OppgaveDeltakereKomponent from '../components/OppgaveDeltakereKomponent.vue';
import OppgaveSvar from '../components/OppgaveSvar.vue';
import {
    readRespondentSvarFromUrl,
    type RespondentSvarUrlParams,
} from '../utils/oppgaveUrl';
import {
    hentOppgaveOversikt,
    type OppgaveData,
} from '../services/oppgaveService';

const OPP_TYPE_VIDERESENDING = 'videresending';
const OPP_TYPE_REISELEDERE = 'reiseledere';
const OPP_TYPE_FYLKESKONTAKTER = 'fylkeskontakter';
const OPP_TYPE_DELTAKERE = 'deltakere';

export default {
    components: { PermanentNotification, OppgaveDeltakereKomponent, OppgaveSvar },

    emits: ['feil'],

    props: {
        aktiv: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            oppgaver: [] as OppgaveData[],
            plId: 0,
            arrangementType: '' as string,
            hentet: false,
            listeLoading: false,
            respondentFraUrl: null as RespondentSvarUrlParams | null,
        };
    },

    computed: {
        erLandArrangement(): boolean {
            return this.arrangementType === 'land';
        },
    },

    watch: {
        aktiv: {
            immediate: true,
            handler(erAktiv: boolean) {
                if (erAktiv) {
                    this.hentAlt(this.hentet);
                }
            },
        },
    },

    mounted() {
        this.synkRespondentFraUrl();
        window.addEventListener('popstate', this.synkRespondentFraUrl);
    },

    unmounted() {
        window.removeEventListener('popstate', this.synkRespondentFraUrl);
    },

    methods: {
        synkRespondentFraUrl(): void {
            this.respondentFraUrl = readRespondentSvarFromUrl();
        },

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
            if (type === OPP_TYPE_DELTAKERE) {
                return 'Deltakere';
            }
            return type;
        },

        async hentAlt(stille = false): Promise<void> {
            if (!stille) {
                this.listeLoading = true;
            }
            try {
                const data = await hentOppgaveOversikt();
                this.oppgaver = data.oppgaver;
                this.plId = data.pl_id;
                this.arrangementType = data.arrangement_type;
                this.hentet = true;
            } catch (e: any) {
                this.$emit('feil', e.message ?? 'Feil ved henting av svar');
            } finally {
                this.listeLoading = false;
            }
        },
    },
};
</script>

<style scoped>
.skjema-skeleton {
    border-radius: var(--radius-high) !important;
}
</style>
