<template>
    <div class="flex gap-2">
        <UButton v-if="caneditgrades" variant="info" size="sm" @click="$emit('edit', id)">{{ mstrings.edit }}</UButton>
        <UButton v-if="!caneditgrades" variant="info" size="sm" @click="$emit('edit', id)">{{ mstrings.view }}</UButton>
        <UButton v-if="caneditgrades" variant="error" size="sm" :disabled="inuse" @click="$emit('delete', id)">{{ mstrings.delete }}</UButton>
        <UButton variant="success" size="sm" @click="$emit('export', id)">{{ mstrings.export }}</UButton>
    </div>
    
</template>

<script setup lang="ts">
    import UButton from '../Common/UButton.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    interface IActionProps {
        id: number;
        caneditgrades: boolean;
        inuse: boolean;
    }

    const props = defineProps< IActionProps >();

    const emits = defineEmits(['edit', 'delete', 'export']);

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
</script>