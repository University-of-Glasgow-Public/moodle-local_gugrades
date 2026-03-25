<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="open_modal">{{ mstrings['exportcapture'] }}</TwButton>

    <VueModal v-model="showexportmodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['exportcapture']">

        <TwAlert color="info">{{  mstrings['exportcapturehelp'] }}</TwAlert>

        <PleaseWait v-if="pleasewait"></PleaseWait>

        <div class="tw:pl-8 tw:mt-5">
            <FormKit
                v-if="!pleasewait"
                type="form"
                :submit-label="mstrings['export']"
                @submit="submit_export_form"
            >
                <TwButton @click.prevent="clickallnone">
                    <span v-if="allnone">Select none</span>
                    <span v-else>Select all</span>
                </TwButton>
                <div class="mb-1">&nbsp;</div>
                <FormKit
                    v-for="option in options"
                    type="checkbox"
                    v-model="option.selected"
                    :label="option.description"
                />
            </FormKit>
        </div>

        <div class="tw:flex tw:justify-end">
            <TwButton color="warning" @click="close_modal">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref } from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import PleaseWait from '@/components/PleaseWait.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import { useToast } from "vue-toastification";
    import { saveAs } from 'file-saver';
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';

    const showexportmodal = ref(false);
    const debug = ref({});
    const allnone = ref(false);
    const pleasewait = ref(false);
    const options = ref([]);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const props = defineProps({
        itemid: Number,
        groupid: Number,
        itemname: String,
        revealnames: Boolean,
    });

    /**
     * Load initial options
     */
    function open_modal() {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        pleasewait.value = false;

        fetchMany([{
            methodname: 'local_gugrades_get_capture_export_options',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
                groupid: props.groupid,
            }
        }])[0]
        .then((result) => {
            options.value = result;
        })
        .catch((error) => {
            showexportmodal.value = false;
            debug.value = error;
        });

        showexportmodal.value = true;
    }

    /**
     * Allnone has been clicked
     */
    function clickallnone() {
        allnone.value = !allnone.value;
        options.value.forEach((option) => {
            option.selected = allnone.value;
        });
    }

    /**
     * Convert options to version required
     * for web service
     */
    function get_data_options(options) {
        let newoptions = [];
        options.forEach((option) => {
            newoptions.push({
                gradetype: option.gradetype,
                other: option.other,
                selected: option.selected
            });
        });

        return newoptions;
    }

    /**
     * Download the pro-forma csv file
     */
    function submit_export_form() {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        pleasewait.value = true;

        fetchMany([{
            methodname: 'local_gugrades_get_capture_export_data',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
                groupid: props.groupid,
                viewfullnames: props.revealnames,
                options: get_data_options(options.value),
            }
        }])[0]
        .then((result) => {
            const csv = result['csv'];
            const d = new Date();
            const filename = props.itemname + '_' + d.toLocaleString() + '.csv';
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
            saveAs(blob, filename);

            showexportmodal.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            showexportmodal.value = false;
            debug.value = error;
        });
    }



    /**
     * Close the modal
     */
    function close_modal() {
        showexportmodal.value = false;
    }
</script>
