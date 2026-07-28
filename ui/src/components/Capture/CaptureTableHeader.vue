<template>
    <div class="flex items-center justify-start w-full gap-2 leading-tight">
        
        <span class="font-semibold break-words" @click="toggleSorting">
            {{ column.description }}
        </span>

        <CaptureColumnEditCog 
            v-if="showeditcog"
            :header="column"
            :itemid="itemid"
            v-bind="$attrs"
            class="shrink-0" 
        />

        <div v-if="showbuttons" class="flex gap-1">
            <UButton size="xs" variant="info" @click="bulksave_clicked">Save</UButton>
            <UButton size="xs" variant="warning" @click="bulkcancel_clicked">Cancel</UButton>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import CaptureColumnEditCog from './CaptureColumnEditCog.vue';
    import UButton from '../Common/UButton.vue';
    import type { HeaderContext } from '@tanstack/vue-table';

    interface iProps {
        column: Record<string, any>; // Keys are strings, values are anything
        headercontext: HeaderContext<any, any>;
        caneditgrades: boolean;
        ineditcellmode: boolean;
        itemid: number;
        editcolumnid: number;
    }

    const props = defineProps< iProps >();

    const emits = defineEmits(['bulksave', 'bulkcancel']);

    const toggleSorting = computed(() => props.headercontext.column.getToggleSortingHandler());

    function bulksave_clicked() {
        emits('bulksave');
    }

    function bulkcancel_clicked() {
        emits('bulkcancel');
    }

    /**
     * Can we show the edit cog?
     * 
     */
    const showeditcog = computed(() => {
        return props.column.editable && !props.ineditcellmode && props.caneditgrades;
    });

    /**
     * Can we show the save/cancel buttons?
     */
    const showbuttons = computed(() => {
        return props.column.editable && props.ineditcellmode && props.caneditgrades && (props.editcolumnid == props.column.id);
    });
</script>