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
            <p class="kjede-hjelp">
                Oppgaveliste for denne respondenten lastes her (kommer snart).
            </p>
        </div>
    </div>
</template>

<script lang="ts">
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

    emits: ['tilbake'],
};
</script>

<style scoped>
.kjede-hjelp {
    font-size: 0.875rem;
    color: var(--color-primary-grey-dark, #666);
    margin-bottom: 0.75rem;
    max-width: 40rem;
}
.oppgave-svar__header {
    margin-bottom: 1.25rem;
}
.oppgave-svar__tittel {
    margin-top: 1rem;
    margin-bottom: 0.25rem;
}
</style>
