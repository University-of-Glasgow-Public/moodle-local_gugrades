<template>
     <DebugDisplay :debug="debug"></DebugDisplay>

    <div id="managemaps">
        <TwAlert v-html="mstrings.conversionhelp" class="my-6"></TwAlert>

        <!-- show available maps -->
        <div v-if="!editmap && loaded">
            <TwAlert v-if="!maps.length" color="warning">{{ mstrings.noconversionmaps }}</TwAlert>

            <EasyDataTable
                v-if="loaded && maps.length"
                :headers="headers"
                :items="maps"
            >
                <template #item-inuse="map">
                    <span v-if="map.inuse">{{ mstrings.yes }}</span>
                    <span v-else>{{ mstrings.no }}</span>
                </template>
                <template #item-actions="map">
                    <div class="py-1" role="group" aria-label="Actions">
                        <button v-if="caneditgrades" class="btn btn-sm mr-1" @click="edit_clicked(map.id)">{{ mstrings.edit }}</button>
                        <button v-if="!caneditgrades" class="btn btn-sm mr-1" @click="edit_clicked(map.id)">{{ mstrings.view }}</button>
                        <button v-if="caneditgrades" class="btn btn-sm btn-error mr-1" :class="{ disabled: map.inuse }" :disabled="map.inuse" @click="delete_clicked(map.id)">{{ mstrings.delete }}</button>
                        <button class="btn btn-sm btn-success mr-1" @click="export_clicked(map.id)">{{ mstrings.export }}</button>
                    </div>
                </template>
            </EasyDataTable>

            <div v-if="caneditgrades" class="mt-4">
                <TwButton color="primary" @click="add_map" class="mr-1">{{ mstrings.addconversionmap }}</TwButton>
                <TwButton color="success" @click="import_clicked">{{ mstrings.importconversionmap }}</TwButton>
            </div>
        </div>

        <!-- Map creation/editing -->
        <div v-if="editmap">
            <EditMap :mapid="editmapid" :caneditgrades="caneditgrades" @close="editmap_closed"></EditMap>
        </div>
    </div>

    <!-- Modal for delete confirm -->
    <ConfirmModal :show="showconfirm" :message="mstrings.deletemapconfirm" @confirm="confirmdelete"></ConfirmModal>

    <!-- Modal for map upload -->
    <VueModal v-model="showimportmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.importconversionmap">

        <TwDropzone :mimetypes="['text/json']" accept="text/json" @onchange="uploadfilechange"></TwDropzone>

        <TwButton color="info" class="mr-1" @click="process_import">{{ mstrings.import }}</TwButton>
        <TwButton color="warning" @click="showimportmodal = false">{{ mstrings.cancel }}</TwButton>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import EditMap from '@/components/Conversion/EditMap.vue';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import { saveAs } from 'file-saver';
    import { useFileDialog } from '@vueuse/core';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwDropzone from '../Tailwind/TwDropzone.vue';
    import type { Header } from "vue3-easy-data-table";

    const maps = ref([]);
    const editmap = ref(false);
    const editmapid = ref(0);
    const loaded = ref(false);
    const showconfirm = ref(false);
    const deletemapid = ref(0);
    const showimportmodal = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const debug = ref({});
    const toast = useToast();
    const headers = ref< Header[] >([]);
    const caneditgrades = ref(false);
    const file = ref<File | null>(null);

    /**
     * Uploaded file has changed
     */
    function uploadfilechange(newfile: File) {
        file.value = newfile;
    }

    /**
     * Get/update the maps
     */
    function get_maps() {

        moodleFetch(
            'local_gugrades_get_conversion_maps',
            {}
        )
        .then((result: any) => {
            maps.value = result;
            loaded.value = true;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Edit button was clicked
     */
    function edit_clicked(mapid: number) {
        editmapid.value = mapid;
        editmap.value = true;
    }

    /**
     * Import button clicked
     */
    function import_clicked() {
        showimportmodal.value = true;
    }

    /**
     * Import json map
     */
    function process_json(jsonmap: string) {

        moodleFetch(
            'local_gugrades_import_conversion_map',
            {
                jsonmap: jsonmap
            }
        )
        .then(() => {
            get_maps();
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Import button on modal clicked
     * Proces selected file.
     */
    function process_import() {
        if (file.value == null) {
            toast.warning('No file to import');
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', (event) => {
            if (event.target) {
                const jsondata = event.target.result as string;
                process_json(jsondata);
            }
            showimportmodal.value = false;
        });
        reader.readAsText(file.value);
    }

    /**
     * EditMap was closed
     */
    function editmap_closed() {
        editmap.value = false;
        get_maps();
    }

    /**
     * Export button was clicked
     */
    function export_clicked(mapid: number) {

        moodleFetch(
            'local_gugrades_get_conversion_map',
            {
                mapid: mapid,
                schedule: '',
            }
        )
        .then((result: any) => {
            const json = JSON.stringify(result, null, 4);
            let filename = result.name + '.json';
            filename = filename.replace(/[/\\?%*:|"<>]/g, '-');
            const blob = new Blob([json], {type: 'text/json;charset=utf-8'});
            saveAs(blob, filename);
            toast.success('Map exported');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Get all the maps for this course
     */
    onMounted(() => {
        headers.value = [
            {text: mstringstore.getMstring('name'), value: 'name'},
            {text: mstringstore.getMstring('scalehead'), value: 'scale'},
            {text: mstringstore.getMstring('maxgrade'), value: 'maxgrade'},
            {text: mstringstore.getMstring('createdby'), value: 'createdby'},
            {text: mstringstore.getMstring('createdat'), value: 'createdat'},
            {text: mstringstore.getMstring('inuse'), value: 'inuse'},
            {text: '', value: 'actions'},
        ];

        // Check editing capability
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:editgrades'
            }
        )
        .then((result: any) => {
            caneditgrades.value = result.hascapability;
        })
        .catch((error) => {
            window.console.log(error);
            debug.value = error;
        });

        get_maps();
    });

    /**
     * Add map button has been pressed
     */
    function add_map() {
        editmap.value = true;
        editmapid.value = 0;
    }

    /**
     * Delete map button has been clicked
     */
    function delete_clicked(mapid: number) {
        showconfirm.value = true;
        deletemapid.value = mapid;
    }

    /**
     * Confirm modal for deletion clicked
     */
    function confirmdelete(confirm: boolean) {

        if (confirm) {

            moodleFetch(
                'local_gugrades_delete_conversion_map',
                {
                    mapid: deletemapid.value,
                }
            )
            .then(() => {
                get_maps();
            })
            .catch((error) => {
                window.console.error(error);
                debug.value = error;
            });
        }

        showconfirm.value = false;
    }
</script>