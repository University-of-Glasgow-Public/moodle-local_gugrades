<template>
    <UButton @click="backclicked">
        <ArrowBigLeft :size="18" /> Back
    </UButton>
</template>

<script setup lang="ts">
    import { ArrowBigLeft } from '@lucide/vue';
    import UButton from '../Common/UButton.vue';

    const emits = defineEmits(['backclick']);

    function backclicked() {
        emits('backclick');
    }
</script>