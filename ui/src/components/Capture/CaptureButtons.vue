<template>
    <div v-if="!loaded" class="tw:w-full tw:h-[40px] tw:skeleton"></div>
    <div v-else class="flex justify-start">
        <ImportButton :enable="!converted && caneditgrades" :itemid="props.itemid" :groupid="props.groupid" :userids="props.userids" :staffuserid="props.staffuserid" @imported="emit('refreshtable')"></ImportButton>
        <CSVImportButton :enable="caneditgrades" :itemid="props.itemid" :groupid="props.groupid" :itemname="props.itemname" :show="props.showcsvimport" :staffuserid="props.staffuserid" @uploaded="emit('refreshtable')"></CSVImportButton>
        <AddMultipleButton :enable="caneditgrades" :itemid="props.itemid"  @editcolumn="multipleclicked"></AddMultipleButton>
        <ReleaseButton :enable="props.gradesimported && caneditgrades" :gradeitemid="props.itemid" :groupid="props.groupid" :released="props.released" @released="emit('refreshtable')"></ReleaseButton>
        <ViewFullNamesButton v-if="props.usershidden"  @viewfullnames="viewfullnames"></ViewFullNamesButton>
        <ConversionButton v-if="props.showconversion && caneditgrades" :itemid="props.itemid" @converted="emit('refreshtable')"></ConversionButton>
        <ExportCaptureButton :itemid="props.itemid" :groupid="props.groupid" :itemname="props.itemname" :revealnames="revealnames"></ExportCaptureButton>
        <InfoButton :itemid="props.itemid" size="xl"></InfoButton>
        <ReloadButton size="3" @refreshtable="refresh_clicked"></ReloadButton>
    </div>
</template>

<script setup>
    import {defineProps, defineEmits} from '@vue/runtime-core';
    import ImportButton from '@/components/Capture/ImportButton.vue';
    import CSVImportButton from '@/components/Capture/CSVImportButton.vue';
    import ReleaseButton from '@/components/Capture/ReleaseButton.vue';
    import ViewFullNamesButton from '@/components/Capture/ViewFullNamesButton.vue';
    import AddMultipleButton from '@/components/Capture/AddMultipleButton.vue';
    import ConversionButton from '@/components/Capture/ConversionButton.vue';
    import InfoButton from '@/components/InfoButton.vue';
    import ReloadButton from '@/components/ReloadButton.vue';
    import ExportCaptureButton from '@/components/Capture/ExportCaptureButton.vue';

    const props = defineProps({
        loaded: {
            type: Boolean,
            default: false,
        },
        itemid: Number,
        groupid: Number,
        userids: Array,
        users: Array,
        itemtype: String,
        itemname: String,
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

    const emit = defineEmits(['viewfullnames', 'refreshtable', 'editcolumn']);

    /**
     * Handle viewfullnames
     * @param bool toggleview
     */
     function viewfullnames(toggleview) {
        emit('viewfullnames', toggleview);
    }

    /**
     * Multiple button has added another column
     * We need to know what it was
     */
    function multipleclicked(cellform) {
        emit('editcolumn', cellform);
    }

    /**
     * Refresh icon was clicked
     */
    function refresh_clicked() {
        emit('refreshtable');
    }

</script>