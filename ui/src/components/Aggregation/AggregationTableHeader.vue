<template>
    <!-- keep all this info together and vertically aligned -->
    <div class="flex flex-col gap-2">
        <UTooltip :text="column.fullname" position="below" class="block">
            <div>
                <span @click="toggleSorting" class="cursor-pointer">{{ column.shortname ?? '' }}</span>
            </div>
            <div class="inline-flex gap-2">
                <div v-if="!infocol && column.showweights">{{ column.weight }}%</div>
                <div v-if="column.gradetype">{{ column.gradetype }} <span v-if="!column.isscale">({{ column.grademax }})</span></div>
            </div>
        </UTooltip>
        <div class="font-light normal-case py-1" v-if="column.strategy">
            <i>{{ column.strategy }}</i>
        </div>
        <div v-if="totaltype">
            ({{ totaltype }})
        </div>
        <InfoButton v-if="column.gradeitemid" :itemid="column.gradeitemid" :text="column.shortname ?? ''" size="lg" color="text-warning"></InfoButton>

        <div v-if="column.isresitgradeitem" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-success text-success-content">{{ mstrings.reassessment}}</div>

        <div v-if="column.categoryid">
            <UButton class="mt-2" size="sm" @click="expand_clicked" aria-label="Drill down into grade category.">
                <ArrowBigRight :size="18" :stroke-width="1" />
            </UButton>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UTooltip from '../Common/UTooltip.vue';
    import UButton from '../Common/UButton.vue';
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

    const toggleSorting = computed(() => props.headercontext.column.getToggleSortingHandler());

    /**
     * Drill down
     */
    function expand_clicked() {
        emits('expandclicked');
    }

</script>