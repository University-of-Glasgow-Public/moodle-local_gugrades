<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <MenuButton @click="open_modal" iconName="SaveAll">
        {{ mstrings['exportcapture'] }}
    </MenuButton>

    <VueModal v-model="showexportmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings['exportcapture']">

        <TwAlert color="info">{{  mstrings['exportcapturehelp'] }}</TwAlert>

        <PleaseWait v-if="pleasewait"></PleaseWait>

        <div class="pl-8 mt-5">
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

        <div class="flex justify-end">
            <TwButton color="warning" @click="close_modal">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import { useToast } from "vue-toastification";
    import { saveAs } from 'file-saver';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { ICaptureExportOptions } from '@/js/Interfaces';
    import MenuButton from '../Common/MenuButton.vue';

    const showexportmodal = ref(false);
    const debug = ref({});
    const allnone = ref(false);
    const pleasewait = ref(false);
    const options = ref< ICaptureExportOptions[] >([]);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const props = defineProps<{
        itemid: number;
        groupid: number;
        itemname: string;
        revealnames: boolean;
    }>();

    /**
     * Load initial options
     */
    function open_modal() {
        pleasewait.value = false;

        moodleFetch(
            'local_gugrades_get_capture_export_options',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
            }
        )
        .then((result: any) => {
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
    function get_data_options(options: ICaptureExportOptions[] ) {
        let newoptions: ICaptureExportOptions[] = [];
        options.forEach((option) => {
            newoptions.push({
                gradetype: option.gradetype,
                selected: option.selected
            });
        });

        return newoptions;
    }

    /**
     * Download the pro-forma csv file
     */
    function submit_export_form() {
        pleasewait.value = true;

        moodleFetch(
            'local_gugrades_get_capture_export_data',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
                viewfullnames: props.revealnames,
                options: get_data_options(options.value),
            }
        )
        .then((result: any) => {
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
