<template>
    <div class="flex items-center justify-start w-full gap-2 leading-tight">
        
        <span class="font-semibold break-words">
            {{ column.description }}
        </span>

        <CaptureColumnEditCog 
            v-if="column.editable && !ineditcellmode && caneditgrades"
            :header="column"
            :itemid="itemid"
            v-bind="$attrs"
            class="shrink-0" 
        />

        <div v-if="column.editable && ineditcellmode && caneditgrades" class="flex gap-1">
            <UButton size="xs" variant="info" @click="bulksave_clicked">Save</UButton>
            <UButton size="xs" variant="warning" @click="bulkcancel_clicked">Cancel</UButton>
        </div>
    </div>
</template>

<script setup lang="ts">
    import CaptureColumnEditCog from './CaptureColumnEditCog.vue';
    import UButton from '../Common/UButton.vue';

    interface iProps {
        column: Record<string, any>; // Keys are strings, values are anything
        caneditgrades: boolean;
        ineditcellmode: boolean;
        itemid: number;
    }

    const props = defineProps< iProps >();

    const emits = defineEmits(['bulksave', 'bulkcancel']);

    function bulksave_clicked() {
        emits('bulksave');
    }

    function bulkcancel_clicked() {
        emits('bulkcancel');
    }
</script>