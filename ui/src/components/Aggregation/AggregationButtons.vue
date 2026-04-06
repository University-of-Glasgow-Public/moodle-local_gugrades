<template>
    <div class="col-12 mt-2">
        <RecalculateButton v-if="caneditgrades" :categoryid="props.categoryid" :staffuserid="props.staffuserid" @recalculated="refreshtable"></RecalculateButton>
        <ConversionButton v-if="!props.toplevel && caneditgrades" :categoryid="props.categoryid" :disabled="!allowconversion" @converted="refreshtable"></ConversionButton>
        <ReleaseCategoryButton v-if="!props.toplevel && caneditgrades"
            :disabled="!props.allowrelease"
            :gradeitemid="props.gradeitemid"
            :groupid="props.groupid"
            :released="props.released"
            @released="refreshtable"
        ></ReleaseCategoryButton>
        <ExportAggregationButton v-if="props.toplevel" :categoryid="props.categoryid" :groupid="props.groupid"></ExportAggregationButton>
    </div>
</template>

<script setup lang="ts">
    import RecalculateButton from '@/components/Aggregation/RecalculateButton.vue';
    import ConversionButton from '@/components/Aggregation/ConversionButton.vue';
    import ReleaseCategoryButton from '@/components/Aggregation/ReleaseCategoryButton.vue';
    import ExportAggregationButton from '@/components/Aggregation/ExportAggregationButton.vue';

    const props = defineProps({
        categoryid: Number,
        gradeitemid: Number,
        groupid: {
            type: Number,
            required: true
        },
        toplevel: Boolean,
        atype: String,
        allowconversion: Boolean,
        allowrelease: Boolean,
        released: Boolean,
        staffuserid: Number,
        caneditgrades: Boolean,
    });

    const emits = defineEmits([
        'refreshtable'
    ]);

    /**
     * Redraw the main table
     */
    function refreshtable() {
        emits('refreshtable');
    }
</script>