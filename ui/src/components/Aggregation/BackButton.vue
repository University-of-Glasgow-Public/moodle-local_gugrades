<template>
    <div class="w-full">
        <div class="mb-1 h-7" aria-hidden="true"></div>
        <div class="mb-2 flex h-8 items-center justify-center">
            <UButton @click="backclicked" class="cursor-pointer">
                <ArrowBigLeft :size="18" /> Back
            </UButton>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { ArrowBigLeft } from '@lucide/vue';
    import UButton from '../Common/UButton.vue';

    const emits = defineEmits(['backclick']);

    function backclicked() {
        emits('backclick');
    }
</script>