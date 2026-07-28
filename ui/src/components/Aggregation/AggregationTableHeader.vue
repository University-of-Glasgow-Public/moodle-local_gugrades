<template>
    <!-- keep all this info together and vertically aligned -->
    <div>
        <UTooltip :text="column.fullname" position="below">
            <div>
                <!-- column title -->
                <!--
                <InfoButton v-if="column.gradeitemid" :itemid="column.gradeitemid" :text="column.shortname ?? ''" size="lg" color="text-warning"></InfoButton>
                -->
                <span @click="toggleSorting">{{ column.shortname ?? '' }}</span>
            </div>
            <div v-if="!infocol && column.showweights">{{ column.weight }}%</div>
            <div v-if="column.gradetype">{{ column.gradetype }} <span v-if="!column.isscale">({{ column.grademax }})</span></div>
            <div v-if="column.isresitgradeitem" class="badge badge-success">{{ mstrings.reassessment}}</div>
        </UTooltip>
        <div class="py-1" v-if="column.strategy">
            <i>{{ column.strategy }}</i>
        </div>
        <div v-if="totaltype">
            ({{ totaltype }})
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UTooltip from '../Common/UTooltip.vue';
    import InfoButton from '../Common/InfoButton.vue';
    import type { IColumn } from '@/js/Interfaces';
    import type { HeaderContext } from '@tanstack/vue-table';

    interface iProps {
        column: IColumn;
        headercontext: HeaderContext<any, any>;
        infocol?: boolean;
        totaltype?: string;
    }

    const props = defineProps< iProps >();

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toggleSorting = computed(() => props.headercontext.column.getToggleSortingHandler());

</script>