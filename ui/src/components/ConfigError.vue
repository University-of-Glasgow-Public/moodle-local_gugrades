<template>
    <UAlert variant="error" class="mt-1">
        <p><b><span v-html="mstrings.configerror"></span>:</b></p>
        <p v-html="errormessage"></p>
    </UAlert>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UAlert from './Common/UAlert.vue';

    const props = defineProps({
        errormessage: String,
    });
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
</script>