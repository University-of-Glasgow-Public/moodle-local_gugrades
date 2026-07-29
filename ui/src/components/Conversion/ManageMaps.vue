<template>
     <DebugDisplay :debug="debug"></DebugDisplay>

    <div id="managemaps">

        <!-- show available maps -->
        <div v-if="!editmap && loaded">
            <UAlert v-if="!maps.length" variant="warning" class="mt-5">{{ mstrings.noconversionmaps }}</UAlert>

            <!-- New TanStack Table -->
            <UTable :data="maps" :columns="columns" class="mt-5"></UTable>

            <div v-if="caneditgrades" class="mt-4 flex gap-2">
                <UButton variant="primary" @click="add_map">{{ mstrings.addconversionmap }}</UButton>
                <UButton variant="success" @click="import_clicked">{{ mstrings.importconversionmap }}</UButton>
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

        <div class="flex gap-2">
            <UButton variant="info" @click="process_import">{{ mstrings.import }}</UButton>
            <UButton variant="warning" @click="showimportmodal = false">{{ mstrings.cancel }}</UButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref, onMounted, h } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import EditMap from '@/components/Conversion/EditMap.vue';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import { saveAs } from 'file-saver';
    import { useFileDialog } from '@vueuse/core';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwDropzone from '../Tailwind/TwDropzone.vue';
    import UTable from '../Common/UTable.vue';
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import ConversionActionButtons from './ConversionActionButtons.vue';
    import { createColumnHelper } from '@tanstack/vue-table';
    import type { IMap } from '@/js/Interfaces.ts';

    interface IHeader {
        text: string;
        value: string;
    }

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
    const headers = ref< IHeader[] >([]);
    const caneditgrades = ref(false);
    const file = ref<File | null>(null);

    const columnHelper = createColumnHelper< IMap >();

    const columns = [
        columnHelper.accessor('name', { 
            header: mstringstore.getMstring('name'), 
        }),

        columnHelper.accessor('scale', {
            header: mstringstore.getMstring('scalehead'),
            cell: (info) => {
                const scale = info.getValue();
                if (scale == 'schedulea') {
                    return 'GGS1';
                } else {
                    return 'GGS2';
                }
            }
        }),

        columnHelper.accessor('maxgrade', {
            header: mstringstore.getMstring('maxgrade'),
        }),

        columnHelper.accessor('createdby', {
            header: mstringstore.getMstring('createdby'),
        }),

        columnHelper.accessor('createdat', {
            header: mstringstore.getMstring('createdat'),
        }),

        columnHelper.accessor('inuse', {
            header: mstringstore.getMstring('inuse'),
            cell: (info) => {
                const inuse = info.getValue();
                return inuse ? 'Yes' : 'No';
            }
        }),

        columnHelper.display({
            id: 'actions',
            header: () => h('span', { class: 'sr-only' }, mstringstore.getMstring('actions') || 'Actions'),
            cell: ({ row }) => {
                const currentMap = row.original 
                return h(ConversionActionButtons, {
                    id: currentMap.id,
                    caneditgrades: caneditgrades.value,
                    inuse: currentMap.inuse,
                    onEdit: (id: number) => edit_clicked(id),
                    onDelete: (id: number) => delete_clicked(id),
                    onExport: (id: number) => export_clicked(id),
                });
            }
        }),
    ];

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
            console.error(error);
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