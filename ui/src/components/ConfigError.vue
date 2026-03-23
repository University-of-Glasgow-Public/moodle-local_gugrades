<template>
    <TwAlert color="error" class="tw:mt-1">
        <p><b><span v-html="mstrings.configerror"></span>:</b></p>
        <p v-html="errormessage"></p>
    </TwAlert>
</template>

<script setup>
    import {defineProps, inject} from 'vue';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';

    const props = defineProps({
        errormessage: String,
    });
    const mstrings = inject('mstrings');
</script>