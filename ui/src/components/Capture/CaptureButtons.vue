<template>
    <div v-if="!loaded" class="w-full h-[40px] skeleton"></div>
    <div v-else class="flex justify-start gap-2">
        <ImportButton :enable="!converted && caneditgrades" :disabledReason="importDisabledReason" :itemid="props.itemid" :groupid="props.groupid" :userids="props.userids" :staffuserid="props.staffuserid" @imported="emit('refreshtable')"></ImportButton>
        <CSVImportButton :enable="caneditgrades" :disabledReason="csvDisabledReason" :itemid="props.itemid" :groupid="props.groupid" :itemname="props.itemname" :show="props.showcsvimport" :staffuserid="props.staffuserid" @uploaded="emit('refreshtable')"></CSVImportButton>
        <AddMultipleButton :enable="caneditgrades" :disabledReason="cannotEditReason" :itemid="props.itemid"  @openmultiple="multipleclicked"></AddMultipleButton>
        <ReleaseButton :enable="props.gradesimported && caneditgrades" :disabledReason="releaseDisabledReason" :gradeitemid="props.itemid" :groupid="props.groupid" :released="props.released" @released="emit('refreshtable')"></ReleaseButton>
        <ViewFullNamesButton v-if="props.usershidden" :revealnames="props.revealnames" @viewfullnames="viewfullnames"></ViewFullNamesButton>
        <ConversionButton v-if="props.showconversion && caneditgrades" :itemid="props.itemid" @converted="emit('refreshtable')"></ConversionButton>
        <ExportCaptureButton :itemid="props.itemid" :groupid="props.groupid" :itemname="props.itemname" :revealnames="revealnames"></ExportCaptureButton>
        <ResetAssessmentButton v-if="caneditgrades" :itemid="props.itemid" @reset="emit('refreshtable')"></ResetAssessmentButton>
        <NameFilterButton :usershidden="props.usershidden && !props.revealnames"></NameFilterButton>
        <InfoButton :itemid="props.itemid" size="xl"></InfoButton>
        <ReloadButton size="3" @refreshtable="refresh_clicked"></ReloadButton>
        <HelpButton class="ml-10" title="Help with capture buttons" subject="capturebuttons"/>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import ImportButton from '@/components/Capture/ImportButton.vue';
    import CSVImportButton from '@/components/Capture/CSVImportButton.vue';
    import ReleaseButton from '@/components/Capture/ReleaseButton.vue';
    import ViewFullNamesButton from '@/components/Capture/ViewFullNamesButton.vue';
    import AddMultipleButton from '@/components/Capture/AddMultipleButton.vue';
    import ConversionButton from '@/components/Capture/ConversionButton.vue';
    import InfoButton from '@/components/Common/InfoButton.vue';
    import ReloadButton from '@/components/Capture/ReloadButton.vue';
    import ExportCaptureButton from '@/components/Capture/ExportCaptureButton.vue';
    import ResetAssessmentButton from '@/components/Capture/ResetAssessmentButton.vue';
    import HelpButton from '../Common/HelpButton.vue';
    import type { IEmitEditColumn } from '@/js/Interfaces';
    import NameFilterButton from '../Common/NameFilterButton.vue';
    import { useMstrings } from '@/stores/mstrings.js';

    const props = defineProps({
        loaded: {
            type: Boolean,
            default: false,
        },
        itemid: {
            type: Number,
            required: true
        },
        groupid: {
            type: Number,
            required: true
        },
        userids: Array,
        users: Array,
        itemtype: String,
        itemname: {
            type: String,
            required: true
        },
        usershidden: Boolean,
        gradesimported: Boolean,
        showconversion: Boolean,
        converted: Boolean,
        released: Boolean,
        revealnames: Boolean,
        showcsvimport: Boolean,
        staffuserid: Number,
        caneditgrades: Boolean,
    });

    const emit = defineEmits(['viewfullnames', 'refreshtable', 'openmultiple']);

    const mstringstore = useMstrings();

    const cannotEditReason = computed(() => {
        return props.caneditgrades ? '' : mstringstore.getMstring('tooltipcannoteditgrades');
    });

    const importDisabledReason = computed(() => {
        if (!props.caneditgrades) {
            return mstringstore.getMstring('tooltipcannoteditgrades');
        }
        if (props.converted) {
            return mstringstore.getMstring('tooltipimportconverted');
        }
        return '';
    });

    const csvDisabledReason = computed(() => {
        if (!props.caneditgrades) {
            return mstringstore.getMstring('tooltipcannoteditgrades');
        }
        if (!props.showcsvimport) {
            return mstringstore.getMstring('tooltipcsvnoidnumber');
        }
        return '';
    });

    const releaseDisabledReason = computed(() => {
        if (!props.caneditgrades) {
            return mstringstore.getMstring('tooltipcannoteditgrades');
        }
        if (!props.gradesimported) {
            return mstringstore.getMstring('tooltipreleasenotimported');
        }
        return '';
    });

    /**
     * Handle viewfullnames
     * @param bool toggleview
     */
     function viewfullnames(toggleview: boolean) {
        emit('viewfullnames', toggleview);
    }

    /**
     * Multiple button has added another column
     * We need to know what it was
     */
    function multipleclicked(columnid: number) {
        emit('openmultiple', columnid);
    }

    /**
     * Refresh icon was clicked
     */
    function refresh_clicked() {
        emit('refreshtable');
    }

</script>