<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" :disabled="!props.show || !enable" @click="showcsvmodal = true">
        {{ mstrings['csvimport'] }}
    </TwButton>

    <VueModal v-model="showcsvmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings['csvimport']">

        <PleaseWait v-if="waiting" progresstype="csvimport" :staffuserid="props.staffuserid"></PleaseWait>

        <!-- Doesn't appear to be a CSV -->
        <TwAlert v-if="incorrectfiletype" color="danger">
            {{ mstrings['incorrectfiletype'] }}
        </TwAlert>

        <div v-if="!incorrectfiletype">

            <!-- Initial download/upload page -->
            <div v-if="pagestate == 'showuploadpage'">
                <p><b>{{  mstrings['csvdownloadhelp'] }}</b></p>

                <TwButton color="primary" @click="csv_download()">{{  mstrings['csvdownload'] }}</TwButton>

                <div class="divider"></div>

                <!-- select file / upload bit -->
                <p><b>{{ mstrings['csvuploadhelp'] }}</b></p>

                <TwDropzone :mimetypes="['text/csv']" accept="text/csv" @onchange="uploadfilechange">CSV files only</TwDropzone>

                <TwButton :disabled="file == null" color="primary" @click="process_selected">{{ mstrings['next'] }}</TwButton>
            </div>

            <!-- Test-run / confirm page -->
            <div v-if="pagestate == 'showtestrun'">
                <p><b>{{ mstrings['csvtestrun'] }}</b></p>
                <EasyDataTable :headers="headers" :items="lines10">
                    <template #item-gradevalue="item">
                        <span v-if="item.grade">{{ item.gradevalue }}</span>
                    </template>
                    <template #item-error="item">
                        <i v-if="item.state < 0" class="text-red-500 fa fa-times" aria-hidden="true"></i>
                        <i v-if="item.state > 0" class="text-green-500 fa fa-check" aria-hidden="true"></i>
                        <i v-if="item.state == 0" class="text-yellow-500 fa fa-info" aria-hidden="true"></i>
                        {{ item.error }}
                    </template>
                </EasyDataTable>
                <p v-if="errorcount" class="text-red-500 mt-1">{{ mstrings['lineswitherrors'] }}: {{ errorcount }}:</p>
                <ul class="text-red-500">
                    <li v-for="error in errorlist" v-key="error.error">
                        <span>{{ error.error }}</span>: <b>{{ error.count }} line(s)</b>
                    </li>
                </ul>

                <!-- submit bit (if no errors) -->
                <div v-if="!errorcount" class="mt-2">
                    <div class="divider"></div>
                    <FormKit type="form" @submit="submit_reason_form">
                        <FormKit
                            type="select"
                            :label="mstrings['reasonforadditionalgrade']"
                            name="reason"
                            v-model="reason"
                            :options="gradetypes"
                            :placeholder="mstrings['selectareason']"
                            validation="required"
                        />
                        <FormKit
                            v-if = 'reason == "OTHER"'
                            :label="mstrings['pleasespecify']"
                            type="text"
                            :placeholder="mstrings['pleasespecify']"
                            name="other"
                            v-model="other"
                        />
                    </FormKit>
                </div>
            </div>

        </div> <!-- incorrectfiletype -->

        <div class="flex justify-end">
            <TwButton color="warning" @click="close_modal()">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, onMounted, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { saveAs } from 'file-saver';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwDropzone from '../Tailwind/TwDropzone.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { IErrorList, IGradetype } from '@/js/Interfaces';

    interface IHeader {
        text: string;
        value: string;
    }

    const showcsvmodal = ref(false);
    const pagestate = ref('showuploadpage');
    const csvcontent = ref('');
    const errorcount = ref(0);
    const errorlist = ref<IErrorList[]>([]);
    const addcount = ref(0);
    const lines = ref([]);
    const headers = ref<IHeader[]>([]);
    const gradetypes = ref<IGradetype[]>([]);
    const reason = ref<string>('');
    const other = ref('');
    const debug = ref({});
    const incorrectfiletype = ref(false);
    const waiting = ref(false);
    const lines10 = computed(() =>{
        return lines.value.slice(0, 10);
    });
    const file = ref<File | null>(null);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const props = defineProps({
        enable: {
            type: Boolean,
            default: true,
        },
        itemid: Number,
        groupid: Number,
        itemname: String,
        show: Boolean,
        staffuserid: Number,
    });

    const emits = defineEmits(['uploaded']);

    /**
     * Uploaded file has changed
     */
    function uploadfilechange(newfile: File) {
        file.value = newfile;
    }

    /**
     * Download the pro-forma csv file
     */
    function csv_download() {

        waiting.value = true;

        moodleFetch(
            'local_gugrades_get_csv_download',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
            }
        )
        .then((result: any) => {
            const csv = result['csv'];
            const d = new Date();
            const filename = props.itemname + '_' + d.toLocaleString() + '.csv';
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
            saveAs(blob, filename);
            waiting.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            showcsvmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Process the uploaded CSV data
     * @param testrun true = don't save the data
     */
    function process_uploaded(testrun: boolean) {

        waiting.value = true;

        moodleFetch(
            'local_gugrades_upload_csv',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
                testrun: testrun,
                reason: reason.value,
                other: other.value,
                csv: csvcontent.value,
            }
        )
        .then((result: any) => {
            lines.value = result.lines;
            errorcount.value = result.errorcount;
            addcount.value = result.addcount;
            errorlist.value = result.errorlist;
            pagestate.value = 'showtestrun';
            waiting.value = false;
            if (!testrun) {
                toast.success(mstrings.value['csvgradesadded'] + ' (' + addcount.value + ')');
                emits('uploaded');
                close_modal();
            }
        })
        .catch((error) => {
            window.console.error(error);
            showcsvmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Get the add grade form stuff
     */
    function get_gradetypes() {

        moodleFetch(
            'local_gugrades_get_gradetypes',
            {
                gradeitemid: props.itemid,
            }
        )
        .then((result: any) => {
            gradetypes.value = result.gradetypes;
        })
        .catch((error) => {
            window.console.error(error);
            showcsvmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Button clicked to upload CSV
     * Process selected file.
     */
     function process_selected() {
        if (file.value == null) {
            toast.warning('No file to import');
            return;
        }

        const type = file.value.type;
        incorrectfiletype.value = type != 'text/csv';

        if (!incorrectfiletype.value) {
            const reader = new FileReader();
            reader.addEventListener('load', (event) => {
                if (event.target) {
                    csvcontent.value = event.target.result as string;

                    process_uploaded(true);
                    get_gradetypes();
                }
            });
            reader.readAsText(file.value);
        }
    }

    /**
     * Submit the final form with reason
     */
    function submit_reason_form() {
        process_uploaded(false);
    }

    onMounted(() => {
        incorrectfiletype.value = false;
        headers.value = [
            {text: mstrings.value['name']!, value: 'name'},
            {text: mstrings.value['idnumber']!, value: 'idnumber'},
            {text: mstrings.value['grade']!, value: 'grade'},
            {text: mstrings.value['gradevalue']!, value: 'gradevalue'},
            {text: mstrings.value['status']!, value: 'error'},
        ];
    });

    /**
     * Close the modal
     */
    function close_modal() {
        incorrectfiletype.value = false;
        showcsvmodal.value = false;
        pagestate.value = 'showuploadpage';
    }
</script>

