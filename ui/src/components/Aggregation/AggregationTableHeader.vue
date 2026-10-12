<template>
    <div class="w-full max-w-44 text-[11px] leading-tight">
        <div class="mb-2 flex items-center justify-between gap-1.5">
            <UTooltip :text="column.fullname" position="below" class="min-w-0 flex-1">
                <button
                    v-if="column.categoryid"
                    type="button"
                    class="inline-flex w-full min-w-0 items-center justify-between gap-1.5 rounded-md border border-slate-300 border-b-2 bg-white px-2 py-1.5 font-semibold text-university-blue shadow-md cursor-pointer hover:bg-slate-100 active:translate-y-px active:shadow-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    @click.stop="expand_clicked"
                    :aria-label="mstringstore.getMstring('expand') + ': ' + column.fullname"
                >
                    <span class="min-w-0 truncate">{{ column.fullname }}</span>
                    <ChevronRight :size="16" :stroke-width="2.5" class="shrink-0" aria-hidden="true" />
                </button>
                <span v-else class="block truncate">{{ column.fullname }}</span>
            </UTooltip>
            <InfoButton v-if="column.gradeitemid" :itemid="column.gradeitemid" :text="column.fullname"></InfoButton>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-x-2 font-normal text-white/80">
            <span v-if="!infocol && column.showweights">{{ column.weight }}%</span>
            <span v-if="column.gradetype">{{ column.gradetype }} <span v-if="!column.isscale">({{ column.grademax }})</span></span>
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
    import { ChevronRight } from '@lucide/vue';

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