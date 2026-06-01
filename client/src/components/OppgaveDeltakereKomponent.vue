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

                <div class="deltakere-sticky-verktoy">
                    <v-select
                        v-model="valgtSporsmal"
                        :items="sporsmalValg"
                        item-title="label"
                        return-object
                        label="Vis svar på spørsmål"
                        variant="outlined"
                        hide-details="auto"
                        :clearable="!sporsmalSvarLaster && !intoleranseImportLaster"
                        :disabled="sporsmalSvarLaster || antallLaster > 0 || intoleranseImportLaster"
                        :loading="sporsmalListeLaster || sporsmalSvarLaster || antallLaster > 0 || intoleranseImportLaster"
                        class="sporsmal-velger"
                        @update:model-value="paSporsmalValgt"
                    />
                    <div
                        v-if="sporsmalSvarLaster"
                        class="sporsmal-henting-status"
                    >
                        <div class="sporsmal-henting-status__rad">
                            <span>Henter svar for alle respondenter…</span>
                            <strong class="sporsmal-henting-status__prosent">{{ sporsmalSvarFremdriftProsent }}%</strong>
                        </div>
                        <v-progress-linear
                            :model-value="sporsmalSvarFremdriftProsent"
                            color="primary"
                            height="6"
                            rounded
                            class="sporsmal-henting-status__bar"
                        />
                    </div>

                    <div
                        v-if="erValgtIntoleranserSporsmal && valgtSporsmal && !sporsmalSvarLaster"
                        class="sporsmal-import"
                    >
                        <v-btn
                            v-if="!intoleranseImportFerdig"
                            variant="outlined"
                            color="primary"
                            class="sporsmal-import__knapp"
                            :loading="intoleranseImportLaster"
                            :disabled="intoleranseImportLaster || !respondenter.length"
                            @click="intoleranseImportBekreftDialog = true"
                        >
                            Importer til brukeren i hele systemet
                        </v-btn>
                        <v-chip
                            v-else
                            size="small"
                            variant="tonal"
                            color="success"
                        >
                            Importert til hele systemet
                        </v-chip>
                        <div
                            v-if="intoleranseImportLaster"
                            class="sporsmal-import__fremdrift"
                        >
                            <div class="sporsmal-henting-status__rad">
                                <span>Importerer for alle respondenter…</span>
                                <strong class="sporsmal-henting-status__prosent">{{ intoleranseImportFremdriftProsent }}%</strong>
                            </div>
                            <v-progress-linear
                                :model-value="intoleranseImportFremdriftProsent"
                                color="primary"
                                height="6"
                                rounded
                                class="sporsmal-henting-status__bar"
                            />
                        </div>
                    </div>
                </div>

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

        <v-dialog v-model="intoleranseImportBekreftDialog" max-width="480" persistent>
            <v-card rounded="lg">
                <v-card-title class="text-h6 pt-5 px-5">
                    <v-icon color="warning" class="mr-2">mdi-alert-outline</v-icon>
                    Importer intoleranser
                </v-card-title>
                <v-card-text class="px-5">
                    Dette vil importere svarene fra oppgaven til hver respondents allergi- og
                    intoleransedata i hele systemet.
                    <strong>Eksisterende lagrede verdier vil bli erstattet.</strong>
                </v-card-text>
                <v-card-actions class="px-5 pb-5">
                    <v-spacer />
                    <v-btn
                        class="v-btn-as v-btn-grey"
                        rounded="large"
                        variant="outlined"
                        :disabled="intoleranseImportLaster"
                        @click="intoleranseImportBekreftDialog = false"
                    >
                        Avbryt
                    </v-btn>
                    <v-btn
                        class="v-btn-as v-btn-hvit"
                        rounded="large"
                        variant="outlined"
                        color="primary"
                        :loading="intoleranseImportLaster"
                        @click="bekreftIntoleranseImport"
                    >
                        Fortsett import
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script lang="ts">
import {
    hentAlleRespondenter,
    hentOppgaveSporsmalListe,
    hentRespondentSporsmalSvar,
    hentRespondentSvarStatus,
    importRespondentIntoleranser,
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

const RESPONDENT_HENTING_BATCH_STORRELSE = 50;

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
            sporsmalSvarLaster: false,
            sporsmalSvarHentet: 0,
            sporsmalSvarTotalt: 0,
            valgtSporsmal: null as SporsmalValgMedKey | null,
            sporsmalHentingId: 0,
            intoleranseImportLaster: false,
            intoleranseImportHentet: 0,
            intoleranseImportTotalt: 0,
            intoleranseImportFerdig: false,
            intoleranseImportBekreftDialog: false,
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
            this.sporsmalSvarLaster = false;
            this.sporsmalSvarHentet = 0;
            this.sporsmalSvarTotalt = 0;
            this.sporsmalHentingId += 1;
            this.nullstillIntoleranseImport();
        },
    },

    computed: {
        sporsmalSvarFremdriftProsent(): number {
            if (this.sporsmalSvarTotalt <= 0) {
                return 0;
            }
            return Math.min(100, Math.round((this.sporsmalSvarHentet / this.sporsmalSvarTotalt) * 100));
        },

        erValgtIntoleranserSporsmal(): boolean {
            return this.valgtSporsmal?.type === 'intoleranser';
        },

        intoleranseImportFremdriftProsent(): number {
            if (this.intoleranseImportTotalt <= 0) {
                return 0;
            }
            return Math.min(100, Math.round((this.intoleranseImportHentet / this.intoleranseImportTotalt) * 100));
        },
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
            const respondenter = this.respondenter;

            for (let i = 0; i < respondenter.length; i += RESPONDENT_HENTING_BATCH_STORRELSE) {
                if (hentingId !== this.statusHentingId) {
                    return;
                }
                const batch = respondenter.slice(i, i + RESPONDENT_HENTING_BATCH_STORRELSE);
                await Promise.all(
                    batch.map((respondent) => this.hentSvarStatusForRespondent(respondent, oppgaveId, hentingId))
                );
            }
        },

        async hentSvarStatusForRespondent(
            respondent: OppgaveRespondentData,
            oppgaveId: number,
            hentingId: number
        ): Promise<void> {
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
                    key: `${s.skjema_type}:${s.skjema_id}:${s.sporsmal_id}`,
                }));
            } catch (e: any) {
                this.sporsmalValg = [];
                this.$emit('feil', e.message ?? 'Kunne ikke hente spørsmål');
            } finally {
                this.sporsmalListeLaster = false;
            }
        },

        async paSporsmalValgt(valgt: SporsmalValgMedKey | null): Promise<void> {
            if (this.sporsmalSvarLaster) {
                return;
            }
            this.valgtSporsmal = valgt;
            this.nullstillSporsmalSvar();
            if (!valgt) {
                return;
            }
            this.sporsmalSvarLaster = true;
            try {
                await this.hentSporsmalSvarForAlle();
            } finally {
                this.sporsmalSvarLaster = false;
                this.sporsmalSvarHentet = 0;
                this.sporsmalSvarTotalt = 0;
            }
        },

        nullstillSporsmalSvar(): void {
            for (const respondent of this.respondenter) {
                respondent.sporsmal_svar = undefined;
            }
            this.nullstillIntoleranseImport();
        },

        nullstillIntoleranseImport(): void {
            this.intoleranseImportLaster = false;
            this.intoleranseImportHentet = 0;
            this.intoleranseImportTotalt = 0;
            this.intoleranseImportFerdig = false;
            this.intoleranseImportBekreftDialog = false;
        },

        async bekreftIntoleranseImport(): Promise<void> {
            await this.importerAlleIntoleranser();
            if (!this.intoleranseImportLaster) {
                this.intoleranseImportBekreftDialog = false;
            }
        },

        async importerAlleIntoleranser(): Promise<void> {
            const sporsmal = this.valgtSporsmal;
            if (!sporsmal || !this.oppgaveId || this.intoleranseImportLaster) {
                return;
            }

            const oppgaveId = this.oppgaveId;
            const { skjema_id: skjemaId, sporsmal_id: sporsmalId } = sporsmal;
            const respondenter = this.respondenter.filter((r) => r.mobil?.trim());

            if (respondenter.length === 0) {
                return;
            }

            this.intoleranseImportLaster = true;
            this.intoleranseImportHentet = 0;
            this.intoleranseImportTotalt = respondenter.length;
            this.intoleranseImportFerdig = false;

            let feilet = 0;

            try {
                for (let i = 0; i < respondenter.length; i += RESPONDENT_HENTING_BATCH_STORRELSE) {
                    const batch = respondenter.slice(i, i + RESPONDENT_HENTING_BATCH_STORRELSE);
                    await Promise.all(
                        batch.map(async (respondent) => {
                            const phone = respondent.mobil.trim();
                            try {
                                await importRespondentIntoleranser(oppgaveId, phone, skjemaId, sporsmalId);
                            } catch {
                                feilet += 1;
                            } finally {
                                this.intoleranseImportHentet += 1;
                            }
                        })
                    );
                }

                this.intoleranseImportFerdig = true;

                if (feilet > 0) {
                    const ok = respondenter.length - feilet;
                    this.$emit(
                        'feil',
                        `Importert ${ok} av ${respondenter.length} respondenter. ${feilet} feilet.`
                    );
                }
            } finally {
                this.intoleranseImportLaster = false;
            }
        },

        async hentSporsmalSvarForAlle(): Promise<void> {
            const sporsmal = this.valgtSporsmal;
            if (!sporsmal || !this.oppgaveId) {
                return;
            }
            const hentingId = ++this.sporsmalHentingId;
            const oppgaveId = this.oppgaveId;
            const { skjema_type: skjemaType, skjema_id: skjemaId, sporsmal_id: sporsmalId } = sporsmal;

            for (const respondent of this.respondenter) {
                respondent.sporsmal_svar = null;
            }

            const respondenter = this.respondenter;
            this.sporsmalSvarTotalt = respondenter.length;
            this.sporsmalSvarHentet = 0;

            for (let i = 0; i < respondenter.length; i += RESPONDENT_HENTING_BATCH_STORRELSE) {
                if (hentingId !== this.sporsmalHentingId) {
                    return;
                }
                const batch = respondenter.slice(i, i + RESPONDENT_HENTING_BATCH_STORRELSE);
                await Promise.all(
                    batch.map((respondent) =>
                        this.hentSporsmalSvarForRespondent(
                            respondent,
                            oppgaveId,
                            skjemaType,
                            skjemaId,
                            sporsmalId,
                            hentingId
                        )
                    )
                );
            }
        },

        async hentSporsmalSvarForRespondent(
            respondent: OppgaveRespondentData,
            oppgaveId: number,
            skjemaType: string,
            skjemaId: number,
            sporsmalId: number,
            hentingId: number
        ): Promise<void> {
            try {
                const phone = respondent.mobil?.trim();
                if (!phone) {
                    if (hentingId === this.sporsmalHentingId) {
                        respondent.sporsmal_svar = { linjer: [{ label: '', value: '—' }], foresatt_godkjent: null };
                    }
                    return;
                }
                try {
                    const svar = await hentRespondentSporsmalSvar(
                        oppgaveId,
                        phone,
                        skjemaType,
                        skjemaId,
                        sporsmalId
                    );
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
            } finally {
                if (hentingId === this.sporsmalHentingId) {
                    this.sporsmalSvarHentet += 1;
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
.deltakere-sticky-verktoy {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #fff;
    padding: 0.75rem 0 1rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 12px -8px rgba(0, 0, 0, 0.2);
}
.deltakere-sticky-verktoy .sporsmal-velger,
.deltakere-sticky-verktoy .sporsmal-henting-status,
.deltakere-sticky-verktoy .sporsmal-import {
    max-width: 36rem;
}
.deltakere-sticky-verktoy .sporsmal-henting-status,
.deltakere-sticky-verktoy .sporsmal-import {
    margin-top: 0.75rem;
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
.sporsmal-henting-status {
    font-size: 0.875rem;
    color: var(--color-primary-grey-dark, #666);
}
.sporsmal-henting-status__rad {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
}
.sporsmal-henting-status__prosent {
    color: var(--color-primary, #1867c0);
    font-variant-numeric: tabular-nums;
}
.sporsmal-henting-status__bar {
    width: 100%;
}
.sporsmal-import__knapp {
    text-transform: none;
    letter-spacing: normal;
}
.sporsmal-import__fremdrift {
    margin-top: 0.5rem;
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
