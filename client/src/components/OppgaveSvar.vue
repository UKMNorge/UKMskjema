<template>
    <div class="oppgave-svar">
        <div class="oppgave-svar__header">
            <v-btn
                class="v-btn-as v-btn-hvit"
                variant="outlined"
                size="small"
                rounded="large"
                prepend-icon="mdi-arrow-left"
                @click="$emit('tilbake')"
            >
                Tilbake til respondenter
            </v-btn>
            <h4 class="oppgave-svar__tittel">{{ respondentNavn }}</h4>
        </div>

        <div v-if="laster" class="oppgave-svar__laster">
            <v-progress-circular indeterminate color="primary" size="32" />
            <span>Laster oppgaveliste…</span>
        </div>

        <template v-else-if="data">
            <div class="paamelding-box infosak big-box text-gnist oppgave-svar__oppgave-boks">
                <strong class="oppgave-svar__oppgave-navn">{{ data.oppgave.name }}</strong>
                <div v-if="data.oppgave.description" class="small text-muted oppgave-svar__beskrivelse">
                    {{ data.oppgave.description }}
                </div>
            </div>

            <p v-if="data.kjede.length === 0" class="text-muted">Ingen skjema i oppgaven.</p>

            <ul v-else class="oppgave-kjede list-unstyled mb-0" role="list">
                <li v-for="(ledd, index) in data.kjede" :key="ledd.ledd_id" class="oppgave-kjede__item">
                    <button
                        type="button"
                        class="oppgave-kjede__knapp text-decoration-none text-body"
                        :class="{ 'oppgave-kjede__knapp--valgt': valgtIndex === index }"
                        @click="velgSkjema(index)"
                    >
                        <div class="paamelding-box skjema-box">
                            <div class="skjema-box__rad">
                                <div class="skjema-box__chips">
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        :color="ledd.besvart ? 'success' : 'warning'"
                                    >
                                        {{ ledd.besvart ? 'Besvart' : 'Ikke besvart' }}
                                    </v-chip>
                                    <v-chip
                                        v-if="ledd.venter_foresatt"
                                        size="small"
                                        variant="tonal"
                                        color="warning"
                                    >
                                        Venter på svar fra foresatt
                                    </v-chip>
                                </div>
                            </div>
                            <div class="oppgave-kjede__tekst text-body">
                                <h5 class="nom">{{ ledd.skjema_navn }}</h5>
                                <span class="small text-muted">{{ ledd.skjema_type_label }}</span>
                            </div>
                        </div>
                    </button>
                </li>
            </ul>

            <div v-if="valgtLedd" class="oppgave-svar__detalj mt-4">
                <div class="paamelding-box big-box oppgave-svar__detalj-boks">
                    <h2 class="mt-0 mb-4">{{ valgtLedd.skjema_navn }}</h2>
                    <hr class="sporsmal-separator" />

                    <template v-if="valgtLedd.detalj.type === 'samtykkeskjema'">
                        <div v-if="!valgtLedd.detalj.versjoner?.length" class="text-muted">
                            Ingen versjoner av samtykkeskjemaet
                        </div>
                        <div v-else class="samtykke-versjoner">
                            <div
                                v-for="(versjon, vi) in valgtLedd.detalj.versjoner"
                                :key="vi"
                                class="mb-4"
                            >
                                <h4>{{ versjon.beskrivelse }}</h4>
                                <div class="samtykke-body" v-html="nl2br(versjon.body_text)"></div>
                            </div>
                        </div>

                        <div v-if="valgtLedd.detalj.svar" class="samtykke-svar as-margin-top-space-3">
                            <v-chip
                                size="small"
                                variant="tonal"
                                :color="valgtLedd.detalj.svar.svar === 'nei' ? 'warning' : 'success'"
                                class="as-margin-bottom-space-2"
                            >
                                {{
                                    valgtLedd.detalj.svar.svar === 'nei'
                                        ? `${respondentNavn} har reservert seg`
                                        : `${respondentNavn} har gitt samtykke`
                                }}
                            </v-chip>
                            <p
                                v-if="
                                    valgtLedd.detalj.svar.skjema_type === 'med-kommentar' &&
                                    valgtLedd.detalj.svar.kommentar
                                "
                                class="mb-1"
                            >
                                <span class="text-muted">Kommentar:</span>
                                {{ valgtLedd.detalj.svar.kommentar }}
                            </p>
                            <p v-if="valgtLedd.detalj.svar.created_at" class="text-muted small mb-0">
                                Tidspunkt: {{ formatTid(valgtLedd.detalj.svar.created_at) }}
                            </p>
                        </div>
                        <p v-else class="text-muted">Ingen samtykke registrert.</p>
                    </template>

                    <template v-else-if="valgtLedd.detalj.type === 'sporreskjema'">
                        <div
                            v-for="sporsmal in valgtLedd.detalj.sporsmal"
                            :key="sporsmal.id"
                            class="oppgave-svar__sporsmal"
                        >
                            <v-chip
                                v-if="sporsmal.foresatt_godkjent !== null"
                                size="small"
                                variant="tonal"
                                :color="sporsmal.foresatt_godkjent ? 'success' : 'warning'"
                                class="as-margin-bottom-space-2"
                            >
                                {{
                                    sporsmal.foresatt_godkjent
                                        ? 'Godkjent av foresatt'
                                        : 'Ikke godkjent av foresatt'
                                }}
                            </v-chip>
                            <label class="bold d-block">{{ sporsmal.tittel }}</label>
                            <div class="oppgave-svar__svar-linjer">
                                <p
                                    v-for="(linje, li) in sporsmal.linjer"
                                    :key="li"
                                    class="mb-1"
                                >
                                    <a
                                        v-if="linje.label === 'Lenke' && linje.value.startsWith('/')"
                                        :href="linje.value"
                                        target="_blank"
                                        rel="noopener"
                                    >Last ned fil</a>
                                    <template v-else-if="linje.label">
                                        <span class="text-muted">{{ linje.label }}:</span>
                                        <v-chip
                                            v-if="erJaNei(linje.value)"
                                            size="x-small"
                                            variant="tonal"
                                            :color="linje.value === 'Ja' ? 'success' : 'grey'"
                                            class="as-margin-left-space-1"
                                        >
                                            {{ linje.value }}
                                        </v-chip>
                                        <template v-else>{{ linje.value }}</template>
                                    </template>
                                    <template v-else>
                                        <v-chip
                                            v-if="erJaNei(linje.value)"
                                            size="x-small"
                                            variant="tonal"
                                            :color="linje.value === 'Ja' ? 'success' : 'grey'"
                                        >
                                            {{ linje.value }}
                                        </v-chip>
                                        <template v-else>{{ linje.value }}</template>
                                    </template>
                                </p>
                            </div>
                            <p v-if="sporsmal.hjelp" class="text-muted help-text">{{ sporsmal.hjelp }}</p>
                            <hr class="sporsmal-separator" />
                        </div>
                    </template>

                    <p v-else class="text-muted">Ukjent skjematype.</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import {
    hentRespondentOppgaveliste,
    type RespondentOppgavelisteResponse,
    type OppgaveSkjemaKjedeVisning,
} from '@/services/oppgaveService';

export default {
    props: {
        oppgaveId: {
            type: Number,
            required: true,
        },
        respondentId: {
            type: Number,
            required: true,
        },
        respondentNavn: {
            type: String,
            required: true,
        },
    },

    emits: ['tilbake', 'feil'],

    data() {
        return {
            laster: true,
            data: null as RespondentOppgavelisteResponse | null,
            valgtIndex: null as number | null,
        };
    },

    computed: {
        valgtLedd(): OppgaveSkjemaKjedeVisning | null {
            if (this.data === null || this.valgtIndex === null) {
                return null;
            }
            return this.data.kjede[this.valgtIndex] ?? null;
        },
    },

    mounted() {
        this.lastInn();
    },

    methods: {
        async lastInn() {
            this.laster = true;
            try {
                this.data = await hentRespondentOppgaveliste(this.oppgaveId, this.respondentId);
                if (this.data.kjede.length > 0) {
                    this.valgtIndex = 0;
                }
            } catch (e) {
                this.$emit('feil', e instanceof Error ? e.message : 'Kunne ikke hente svar');
            } finally {
                this.laster = false;
            }
        },

        velgSkjema(index: number) {
            this.valgtIndex = this.valgtIndex === index ? null : index;
        },

        indicatorColor(indicator: string): string {
            if (indicator === 'success') {
                return 'success';
            }
            if (indicator === 'warning') {
                return 'warning';
            }
            return 'error';
        },

        indicatorIcon(indicator: string): string {
            if (indicator === 'success') {
                return 'mdi-check-circle';
            }
            if (indicator === 'warning') {
                return 'mdi-clock-outline';
            }
            return 'mdi-close-circle';
        },

        erJaNei(value: string): boolean {
            return value === 'Ja' || value === 'Nei';
        },

        nl2br(text: string): string {
            if (!text) {
                return '';
            }
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        },

        formatTid(raw: string): string {
            const d = new Date(raw);
            if (Number.isNaN(d.getTime())) {
                return raw;
            }
            const pad = (n: number) => String(n).padStart(2, '0');
            return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
    },
};
</script>

<style scoped>
.oppgave-svar__header {
    margin-bottom: 1.25rem;
}
.oppgave-svar__tittel {
    margin-top: 1rem;
    margin-bottom: 0.25rem;
}
.oppgave-svar__laster {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--color-primary-grey-dark, #666);
}
.oppgave-svar__oppgave-boks {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: #fff;
}
.oppgave-svar__oppgave-navn {
    font-size: 1.1rem;
}
.oppgave-svar__beskrivelse {
    margin-top: 0.25rem;
}
.list-unstyled {
    list-style: none;
    padding-left: 0;
}
.oppgave-kjede__item {
    margin-bottom: 0.5rem;
    overflow: hidden !important;
    border-radius: 10px;
}
.oppgave-kjede__knapp {
    display: block;
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
    text-align: left;
    cursor: pointer;
}
.oppgave-kjede__knapp--valgt .skjema-box {
    opacity: 1;
    background: #e3edf7;
    border: solid 2px #1867c0;
}
.skjema-box {
    padding: 15px;
    min-height: 70px;
    border-radius: 10px;
    background: #fff;
    margin-bottom: 0;
}
.skjema-box__rad {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.skjema-box__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    flex: 1;
    min-width: 0;
}
.skjema-box__indikator {
    flex-shrink: 0;
}
.oppgave-kjede__tekst h5.nom {
    margin: 0;
    font-size: 1rem;
}
.oppgave-svar__detalj-boks {
    padding: 1.25rem;
    background: #fff;
    border-radius: 8px;
}
.sporsmal-separator {
    border: 0;
    border-top: 1px solid #dee2e6;
    margin: 1rem 0;
}
.oppgave-svar__sporsmal .bold {
    font-weight: 600;
}
.help-text {
    font-size: 0.875rem;
}
.samtykke-body {
    white-space: pre-wrap;
}
@media (max-width: 575px) {
    .skjema-box {
        min-height: 60px !important;
    }
}
</style>
