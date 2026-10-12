<template>
    <div class="w-full max-w-44 text-[11px] leading-tight">
        <div class="flex items-center justify-center gap-1">
            <UTooltip :text="column.fullname" position="below" class="min-w-0">
                <span class="break-words">{{ column.shortname ?? '' }}</span>
            </UTooltip>
            <button
                v-if="column.categoryid"
                class="shrink-0 cursor-pointer rounded p-0.5 hover:bg-white/10"
                @click.stop="expand_clicked"
                aria-label="Drill down into grade category."
            >
                <ArrowBigRight :size="16" :stroke-width="1.5" />
            </button>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-x-2 font-normal text-white/80">
            <span v-if="!infocol && column.showweights">{{ column.weight }}%</span>
            <span v-if="column.gradetype">{{ column.gradetype }} <span v-if="!column.isscale">({{ column.grademax }})</span></span>
            <InfoButton v-if="column.gradeitemid" :itemid="column.gradeitemid" :text="column.shortname ?? ''"></InfoButton>
        </div>
        <div class="font-normal text-white/80" v-if="column.strategy"><i>{{ column.strategy }}</i></div>
        <div v-if="totaltype" class="font-normal text-white/80">({{ totaltype }})</div>
        <div v-if="column.isresitgradeitem" class="font-normal text-white/80">{{ mstrings.reassessment }}</div>
    </div>
</template>


<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UTooltip from '../Common/UTooltip.vue';
    import InfoButton from '../Common/InfoButton.vue';
    import type { IColumn } from '@/js/Interfaces';
    import type { HeaderContext } from '@tanstack/vue-table';
    import { ArrowBigRight } from '@lucide/vue';

    interface iProps {
        column: IColumn;
        headercontext: HeaderContext<any, any>;
        infocol?: boolean;
        totaltype?: string;
    }

    const props = defineProps< iProps >();

    const emits = defineEmits(['expandclicked']);

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Drill down
     */
    function expand_clicked() {
        emits('expandclicked');
    }

</script>