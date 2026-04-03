<template>
    <TwAlert color="error" class="tw:mt-1">
        <p><b><span v-html="mstrings.configerror"></span>:</b></p>
        <p v-html="errormessage"></p>
    </TwAlert>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';

    const props = defineProps({
        errormessage: String,
    });
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
</script>