<template>
    <div class="oppgave-deltakere as-margin-top-space-4">
        <button
            type="button"
            class="deltakere-header"
            :aria-expanded="utvidet"
            @click="toggleUtvidet"
        >
            <span class="kjede-tittel">Respondenter</span>
            <v-icon size="small" class="deltakere-header__pil">
                {{ utvidet ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
            </v-icon>
        </button>

        <v-expand-transition>
            <div v-if="utvidet" class="deltakere-innhold">
                <p class="kjede-hjelp">
                    Klikk status for å filtrere listen. Velg et spørsmål for å vise svaret på hver respondent her.
                    Klikk en respondent for å åpne alle svarene i et nytt vindu.
                </p>

                <v-select
                    v-if="sporsmalValg.length"
                    v-model="valgtSporsmal"
                    :items="sporsmalValg"
                    item-title="label"
                    return-object
                    label="Vis svar på spørsmål"
                    variant="outlined"
                    hide-details="auto"
                    clearable
                    :loading="sporsmalListeLaster"
                    class="sporsmal-velger as-margin-bottom-space-3"
                    @update:model-value="paSporsmalValgt"
                />

                <v-skeleton-loader
                    v-if="loading"
                    type="list-item-two-line"
                    class="as-margin-bottom-space-2"
                />

                <template v-else-if="respondenter.length">
            <div class="status-oppsummering as-margin-bottom-space-3">
                <v-chip
                    class="status-oppsummering__chip status-oppsummering__chip--klikkbar"
                    size="small"
                    :variant="filterChipVariant(!harStatusfilter)"
                    color="primary"
                    role="button"
                    tabindex="0"
                    @click="velgAlleStatus"
                    @keyup.enter="velgAlleStatus"
                >
                    Totalt: {{ respondenter.length }}
                </v-chip>
                <v-chip
                    v-if="antallLaster > 0"
                    class="status-oppsummering__chip status-oppsummering__chip--klikkbar"
                    size="small"
                    :variant="filterChipVariant(erStatusValgt('laster'))"
                    color="grey"
                    role="button"
                    tabindex="0"
                    @click="toggleStatusFilter('laster')"
                    @keyup.enter="toggleStatusFilter('laster')"
                >
                    Laster: {{ antallLaster }}
                </v-chip>
                <v-chip
                    v-for="s in statusOppsummeringTyper"
                    :key="s.status"
                    class="status-oppsummering__chip status-oppsummering__chip--klikkbar"
                    size="small"
                    :variant="filterChipVariant(erStatusValgt(s.status))"
                    :color="s.color"
                    role="button"
                    tabindex="0"
                    @click="toggleStatusFilter(s.status)"
                    @keyup.enter="toggleStatusFilter(s.status)"
                >
                    {{ s.label }}: {{ tellStatus(s.status) }}
                </v-chip>
                <v-chip
                    v-if="antallNominasjon > 0"
                    class="status-oppsummering__chip status-oppsummering__chip--klikkbar"
                    size="small"
                    :variant="filterChipVariant(filterKunNominasjon)"
                    color="primary"
                    prepend-icon="mdi-account-arrow-right-outline"
                    role="button"
                    tabindex="0"
                    @click="toggleNominasjonFilter"
                    @keyup.enter="toggleNominasjonFilter"
                >
                    Nominasjon: {{ antallNominasjon }}
                </v-chip>
            </div>

            <p
                v-if="harStatusfilter && filtrerteRespondenter.length === 0"
                class="tom-kjede as-margin-bottom-space-2"
            >
                Ingen respondenter med valgt status.
            </p>

            <div
                v-else
                class="deltaker-liste"
            >
            <div
                v-for="r in filtrerteRespondenter"
                :key="r.id"
                class="deltaker-blokk"
            >
                <div
                    class="deltaker-rad deltaker-rad--klikkbar"
                    role="button"
                    tabindex="0"
                    @click="apneRespondent(r)"
                    @keyup.enter="apneRespondent(r)"
                >
                    <div class="deltaker-rad__hoved">
                        <div class="deltaker-rad__info">
                            <span class="deltaker-rad__navn">{{ r.navn }} {{ r.etternavn }} ({{ r.mobil }})</span>
                            <span
                                v-if="r.fylke || r.arrangement || r.foresatt_mobil"
                                class="deltaker-rad__meta"
                            >
                                <template v-if="r.fylke">{{ r.fylke }}</template>
                                <template v-if="r.fylke && r.arrangement"> · </template>
                                <template v-if="r.arrangement">{{ r.arrangement }}</template>
                                <template v-if="r.foresatt_mobil">
                                    <template v-if="r.fylke || r.arrangement"> · </template>
                                </template>
                            </span>
                        </div>
                        <v-chip
                            v-if="r.videresending_nominasjon"
                            size="small"
                            variant="outlined"
                            color="primary"
                        >
                            Nominasjon
                        </v-chip>
                    </div>
                    <div class="deltaker-rad__hoyre">
                        <v-progress-circular
                            v-if="r.svar_status === null"
                            indeterminate
                            size="18"
                            width="2"
                            color="primary"
                        />
                        <v-chip
                            v-else
                            size="small"
                            variant="tonal"
                            :color="svarStatusColor(r.svar_status)"
                        >
                            {{ svarStatusLabel(r.svar_status) }}
                        </v-chip>
                        <v-icon size="small" class="deltaker-rad__pil">mdi-chevron-right</v-icon>
                    </div>
                </div>
                <div
                    v-if="valgtSporsmal"
                    class="deltaker-rad__sporsmal-svar"
                    @click.stop
                >
                    <v-progress-circular
                        v-if="r.sporsmal_svar === null"
                        indeterminate
                        size="16"
                        width="2"
                        color="primary"
                    />
                    <template v-else-if="r.sporsmal_svar">
                        <v-chip
                            v-if="r.sporsmal_svar.foresatt_godkjent !== null"
                            size="x-small"
                            variant="tonal"
                            :color="r.sporsmal_svar.foresatt_godkjent ? 'success' : 'warning'"
                            class="as-margin-bottom-space-1"
                        >
                            {{
                                r.sporsmal_svar.foresatt_godkjent
                                    ? 'Godkjent av foresatt'
                                    : 'Ikke godkjent av foresatt'
                            }}
                        </v-chip>
                        <div class="deltaker-rad__svar-linjer">
                            <p
                                v-for="(linje, li) in r.sporsmal_svar.linjer"
                                :key="li"
                                class="deltaker-rad__svar-linje"
                            >
                                <a
                                    v-if="linje.label === 'Lenke' && linje.value.startsWith('/')"
                                    :href="linje.value"
                                    target="_blank"
                                    rel="noopener"
                                >Last ned fil</a>
                                <template v-else-if="linje.label">
                                    <span class="deltaker-rad__svar-label">{{ linje.label }}:</span>
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
                    </template>
                </div>
            </div>
            </div>
                </template>
                <p v-else class="tom-kjede">Ingen respondenter på denne oppgaven ennå.</p>
            </div>
        </v-expand-transition>
    </div>
</template>

<script lang="ts">
import {
    hentAlleRespondenter,
    hentOppgaveSporsmalListe,
    hentRespondentSporsmalSvar,
    hentRespondentSvarStatus,
    type OppgaveSporsmalValg,
} from '../services/oppgaveService';
import OppgaveRespondent, {
    type OppgaveRespondentData,
    type OppgaveSvarStatus,
    OPPGAVE_SVAR_STATUS_FULLFORT,
    OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT,
    OPPGAVE_SVAR_STATUS_PABEGYNT,
    OPPGAVE_SVAR_STATUS_UKJENT,
    OPPGAVE_SVAR_STATUS_VENTER_FORESATT,
    oppgaveSvarStatusColor,
    oppgaveSvarStatusLabel,
} from '../objects/OppgaveRespondent';
import { openRespondentSvarWindow } from '../utils/oppgaveUrl';

type StatusFilterKey = OppgaveSvarStatus | 'laster';

type SporsmalValgMedKey = OppgaveSporsmalValg & { key: string };

const SPORSMAL_SVAR_BATCH_STORRELSE = 10;

const STATUS_OPPSUMMERING_TYPER: { status: OppgaveSvarStatus; label: string; color: string }[] = [
    { status: OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_IKKE_PABEGYNT) },
    { status: OPPGAVE_SVAR_STATUS_PABEGYNT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_PABEGYNT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_PABEGYNT) },
    { status: OPPGAVE_SVAR_STATUS_VENTER_FORESATT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_VENTER_FORESATT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_VENTER_FORESATT) },
    { status: OPPGAVE_SVAR_STATUS_FULLFORT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_FULLFORT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_FULLFORT) },
    { status: OPPGAVE_SVAR_STATUS_UKJENT, label: oppgaveSvarStatusLabel(OPPGAVE_SVAR_STATUS_UKJENT), color: oppgaveSvarStatusColor(OPPGAVE_SVAR_STATUS_UKJENT) },
];

function tilRespondentData(r: OppgaveRespondent, svarStatus: OppgaveSvarStatus | null = null): OppgaveRespondentData {
    return {
        id: r.id,
        navn: r.navn,
        etternavn: r.etternavn,
        mobil: r.mobil,
        videresending_nominasjon: r.videresending_nominasjon,
        fylke: r.fylke,
        arrangement: r.arrangement,
        foresatt_mobil: r.foresatt_mobil,
        svar_status: svarStatus,
    };
}

export default {
    props: {
        oppgaveId: {
            type: Number,
            required: true,
        },
    },

    emits: ['feil'],

    data() {
        return {
            utvidet: false,
            respondenterHentet: false,
            loading: false,
            respondenter: [] as OppgaveRespondentData[],
            statusHentingId: 0,
            valgteStatusFilter: [] as StatusFilterKey[],
            filterKunNominasjon: false,
            sporsmalValg: [] as SporsmalValgMedKey[],
            sporsmalListeLaster: false,
            valgtSporsmal: null as SporsmalValgMedKey | null,
            sporsmalHentingId: 0,
        };
    },

    watch: {
        oppgaveId() {
            this.utvidet = false;
            this.respondenterHentet = false;
            this.respondenter = [];
            this.valgteStatusFilter = [];
            this.filterKunNominasjon = false;
            this.statusHentingId += 1;
            this.sporsmalValg = [];
            this.valgtSporsmal = null;
            this.sporsmalHentingId += 1;
        },
    },

    computed: {
        statusOppsummeringTyper(): typeof STATUS_OPPSUMMERING_TYPER {
            return STATUS_OPPSUMMERING_TYPER;
        },

        antallLaster(): number {
            return this.respondenter.filter((r) => r.svar_status === null).length;
        },

        antallNominasjon(): number {
            return this.respondenter.filter((r) => r.videresending_nominasjon).length;
        },

        harStatusfilter(): boolean {
            return this.valgteStatusFilter.length > 0 || this.filterKunNominasjon;
        },

        filtrerteRespondenter(): OppgaveRespondentData[] {
            let liste = this.respondenter;
            if (this.filterKunNominasjon) {
                liste = liste.filter((r) => r.videresending_nominasjon);
            }
            if (this.valgteStatusFilter.length > 0) {
                liste = liste.filter((r) => this.respondentMatcherFilter(r));
            }
            return liste;
        },
    },

    methods: {
        async toggleUtvidet(): Promise<void> {
            this.utvidet = !this.utvidet;
            if (this.utvidet && !this.respondenterHentet) {
                await this.hentRespondenter();
            }
        },

        tellStatus(status: OppgaveSvarStatus): number {
            return this.respondenter.filter((r) => r.svar_status === status).length;
        },

        respondentMatcherFilter(respondent: OppgaveRespondentData): boolean {
            if (respondent.svar_status === null || respondent.svar_status === undefined) {
                return this.valgteStatusFilter.includes('laster');
            }
            return this.valgteStatusFilter.includes(respondent.svar_status);
        },

        erStatusValgt(key: StatusFilterKey): boolean {
            return this.valgteStatusFilter.includes(key);
        },

        /** Vuetify: flat = fylt med farge, outlined = kun ramme. */
        filterChipVariant(valgt: boolean): 'flat' | 'outlined' {
            return valgt ? 'flat' : 'outlined';
        },

        velgAlleStatus(): void {
            this.valgteStatusFilter = [];
            this.filterKunNominasjon = false;
        },

        toggleNominasjonFilter(): void {
            this.filterKunNominasjon = !this.filterKunNominasjon;
        },

        toggleStatusFilter(key: StatusFilterKey): void {
            const idx = this.valgteStatusFilter.indexOf(key);
            if (idx === -1) {
                this.valgteStatusFilter = [...this.valgteStatusFilter, key];
            } else {
                this.valgteStatusFilter = this.valgteStatusFilter.filter((k) => k !== key);
            }
        },

        async hentRespondenter(): Promise<void> {
            if (!this.oppgaveId) {
                this.respondenter = [];
                this.valgteStatusFilter = [];
                return;
            }
            this.loading = true;
            this.valgteStatusFilter = [];
            this.filterKunNominasjon = false;
            try {
                const hentet = await hentAlleRespondenter(this.oppgaveId);
                this.respondenter = hentet.map((r) => tilRespondentData(r, null));
                this.respondenterHentet = true;
                this.loading = false;
                await Promise.all([this.hentSvarStatusForAlle(), this.hentSporsmalListe()]);
            } catch (e: any) {
                this.respondenter = [];
                this.respondenterHentet = false;
                this.$emit('feil', e.message ?? 'Kunne ikke hente respondenter');
                this.loading = false;
            }
        },

        async hentSvarStatusForAlle(): Promise<void> {
            const oppgaveId = this.oppgaveId;
            const hentingId = ++this.statusHentingId;
            await Promise.all(
                this.respondenter.map(async (respondent) => {
                    try {
                        const status = await hentRespondentSvarStatus(oppgaveId, respondent.mobil);
                        if (hentingId !== this.statusHentingId) {
                            return;
                        }
                        respondent.svar_status = status;
                    } catch {
                        if (hentingId === this.statusHentingId) {
                            respondent.svar_status = OPPGAVE_SVAR_STATUS_UKJENT;
                        }
                    }
                })
            );
        },

        svarStatusLabel(status: OppgaveSvarStatus | null | undefined): string {
            if (status === null || status === undefined) {
                return '';
            }
            return oppgaveSvarStatusLabel(status);
        },

        svarStatusColor(status: OppgaveSvarStatus | null | undefined): string {
            if (status === null || status === undefined) {
                return 'grey';
            }
            return oppgaveSvarStatusColor(status);
        },

        apneRespondent(respondent: OppgaveRespondentData): void {
            const phone = respondent.mobil?.trim();
            if (!phone) {
                return;
            }
            openRespondentSvarWindow(this.oppgaveId, phone);
        },

        async hentSporsmalListe(): Promise<void> {
            if (!this.oppgaveId) {
                this.sporsmalValg = [];
                return;
            }
            this.sporsmalListeLaster = true;
            try {
                const liste = await hentOppgaveSporsmalListe(this.oppgaveId);
                this.sporsmalValg = liste.map((s) => ({
                    ...s,
                    key: `${s.skjema_id}:${s.sporsmal_id}`,
                }));
            } catch (e: any) {
                this.sporsmalValg = [];
                this.$emit('feil', e.message ?? 'Kunne ikke hente spørsmål');
            } finally {
                this.sporsmalListeLaster = false;
            }
        },

        paSporsmalValgt(valgt: SporsmalValgMedKey | null): void {
            this.valgtSporsmal = valgt;
            this.nullstillSporsmalSvar();
            if (valgt) {
                void this.hentSporsmalSvarForAlle();
            }
        },

        nullstillSporsmalSvar(): void {
            for (const respondent of this.respondenter) {
                respondent.sporsmal_svar = undefined;
            }
        },

        async hentSporsmalSvarForAlle(): Promise<void> {
            const sporsmal = this.valgtSporsmal;
            if (!sporsmal || !this.oppgaveId) {
                return;
            }
            const hentingId = ++this.sporsmalHentingId;
            const oppgaveId = this.oppgaveId;
            const { skjema_id: skjemaId, sporsmal_id: sporsmalId } = sporsmal;

            for (const respondent of this.respondenter) {
                respondent.sporsmal_svar = null;
            }

            const respondenter = this.respondenter;
            for (let i = 0; i < respondenter.length; i += SPORSMAL_SVAR_BATCH_STORRELSE) {
                if (hentingId !== this.sporsmalHentingId) {
                    return;
                }
                const batch = respondenter.slice(i, i + SPORSMAL_SVAR_BATCH_STORRELSE);
                await Promise.all(
                    batch.map((respondent) =>
                        this.hentSporsmalSvarForRespondent(respondent, oppgaveId, skjemaId, sporsmalId, hentingId)
                    )
                );
            }
        },

        async hentSporsmalSvarForRespondent(
            respondent: OppgaveRespondentData,
            oppgaveId: number,
            skjemaId: number,
            sporsmalId: number,
            hentingId: number
        ): Promise<void> {
            const phone = respondent.mobil?.trim();
            if (!phone) {
                if (hentingId === this.sporsmalHentingId) {
                    respondent.sporsmal_svar = { linjer: [{ label: '', value: '—' }], foresatt_godkjent: null };
                }
                return;
            }
            try {
                const svar = await hentRespondentSporsmalSvar(oppgaveId, phone, skjemaId, sporsmalId);
                if (hentingId !== this.sporsmalHentingId) {
                    return;
                }
                respondent.sporsmal_svar = {
                    linjer: svar.linjer,
                    foresatt_godkjent: svar.foresatt_godkjent,
                };
            } catch {
                if (hentingId === this.sporsmalHentingId) {
                    respondent.sporsmal_svar = {
                        linjer: [{ label: '', value: 'Kunne ikke hente svar' }],
                        foresatt_godkjent: null,
                    };
                }
            }
        },

        erJaNei(value: string): boolean {
            return value === 'Ja' || value === 'Nei';
        },
    },
};
</script>

<style scoped>
.kjede-tittel {
    font-weight: 600;
    margin-bottom: 0.35rem;
}
.kjede-hjelp {
    font-size: 0.875rem;
    color: var(--color-primary-grey-dark, #666);
    margin-bottom: 0.75rem;
    max-width: 40rem;
}
.tom-kjede {
    color: var(--color-primary-grey-dark, #666);
    font-style: italic;
}
.oppgave-deltakere {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    padding-top: 1rem;
}
.deltakere-header {
    display: block;
    width: 100%;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    text-align: center;
}
.deltakere-header:focus-visible {
    outline: 2px solid rgba(25, 118, 210, 0.45);
    outline-offset: 2px;
    border-radius: 4px;
}
.deltakere-header__pil {
    opacity: 0.55;
    flex-shrink: 0;
}
.deltakere-innhold {
    padding-top: 0.75rem;
}
.status-oppsummering {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}
.status-oppsummering__chip {
    font-weight: 600;
}
.status-oppsummering__chip--klikkbar {
    cursor: pointer;
    user-select: none;
}
.status-oppsummering__chip--klikkbar:focus-visible {
    outline: 2px solid rgba(25, 118, 210, 0.45);
    outline-offset: 2px;
}
.status-oppsummering__chip.v-chip--variant-outlined {
    background: transparent;
}
.sporsmal-velger {
    max-width: 36rem;
}
.deltaker-liste {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.deltaker-blokk {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.deltaker-rad {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.6rem 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: var(--radius-high, 10px);
    background: rgba(255, 255, 255, 0.6);
}
.deltaker-rad--klikkbar {
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
}
.deltaker-rad--klikkbar:hover,
.deltaker-rad--klikkbar:focus-visible {
    border-color: rgba(25, 118, 210, 0.45);
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12);
    background: rgba(255, 255, 255, 0.95);
    outline: none;
}
.deltaker-rad__hoved {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
}
.deltaker-rad__hoved .v-chip {
    flex-shrink: 0;
    min-width: fit-content;
}
.deltaker-rad__info {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}
.deltaker-rad__navn {
    font-weight: 700;
    font-size: 0.95rem;
    min-width: 0;
}
.deltaker-rad__meta {
    font-size: 0.8rem;
    color: var(--color-primary-grey-dark, #666);
    min-width: 0;
}
.deltaker-rad__nominasjon-chip {
    align-self: flex-start;
}
.deltaker-rad__hoyre {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}
.deltaker-rad__pil {
    opacity: 0.45;
    flex-shrink: 0;
}
.deltaker-rad__sporsmal-svar {
    margin: 0 0.75rem 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-top: none;
    border-radius: 0 0 var(--radius-high, 10px) var(--radius-high, 10px);
    background: rgba(25, 118, 210, 0.04);
    font-size: 0.875rem;
}
.deltaker-rad__svar-linjer {
    margin: 0;
}
.deltaker-rad__svar-linje {
    margin: 0 0 0.25rem;
}
.deltaker-rad__svar-linje:last-child {
    margin-bottom: 0;
}
.deltaker-rad__svar-label {
    color: var(--color-primary-grey-dark, #666);
    margin-right: 0.25rem;
}
@media (max-width: 768px) {
    .deltaker-rad__navn {
        font-size: 0.85rem;
    }
    .deltaker-rad__meta {
        font-size: 0.75rem;
    }
}
</style>
