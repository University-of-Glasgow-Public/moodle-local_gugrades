<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" class="mr-1" @click="conversion_clicked()">{{ mstrings.convertgrades }}</TwButton>

    <VueModal v-model="showselectmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.conversionselect">

        <PleaseWait v-if="waiting"></PleaseWait>

        <div v-if="!waiting">

            <!-- Show the selected map name (if there is one)-->
            <p v-if="mapname" class="mb-2">
                {{ mstrings.selectedmap }}: <b>{{ mapname }}</b>
            </p>

            <!--  If no map is currently selected, show the selection dialogue -->
            <div v-if="!selection">
                <TwAlert v-if="nomaps && loaded" class="warning">{{ mstrings.nomaps }}</TwAlert>

                <EasyDataTable class="mb-2" v-if="!nomaps && loaded" :items="maps" :headers="headers" :hide-footer="true">
                    <template #item-select="item">
                        <input type="radio" :value="item.id" v-model="mapid"/>
                    </template>
                </EasyDataTable>

                <div>
                    <TwButton color="primary" class="mr-1" @click="save_clicked" :disabled="mapid == 0">{{ mstrings.save }}</TwButton>
                    <TwButton color="warning" @click="showselectmodal = false">{{  mstrings.cancel }}</TwButton>
                </div>
            </div>

            <!-- if a map is selected then show warning message and option to remove -->
            <div v-if="selection">
                <TwAlert color="warning">{{ mstrings.conversionremovewarning }}</TwAlert>
                <div class="mt-1 mb-4">
                    <TwButton color="primary" class="mr-1" @click="remove_clicked">{{ mstrings.remove }}</TwButton>
                    <TwButton color="warning" @click="showselectmodal = false">{{  mstrings.cancel }}</TwButton>
                </div>
            </div>

        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useToast } from "vue-toastification";
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import type { Header } from "vue3-easy-data-table";
    import type { IMap } from '@/js/Interfaces';

    const maps = ref< IMap[] >([]);
    const nomaps = ref(true);
    const loaded = ref(false);
    const selection = ref(0);
    const mapid = ref(0);
    const showselectmodal = ref(false);
    const mapname = ref('');
    const debug = ref({});
    const waiting = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const headers = ref< Header[] >([
        {text: mstringstore.getMstring('select'), value: 'select'},
        {text: mstringstore.getMstring('name'), value: 'name'},
        {text: mstringstore.getMstring('scale'), value: 'scale'},
    ]);

    const props = defineProps({
        categoryid: Number,
    });

    const emits = defineEmits(['converted']);

    /**
     * Get maps
     */
     function get_maps() {

        moodleFetch(
            'local_gugrades_get_conversion_maps',
            {}
        )
        .then((result: any) => {
            maps.value = result;
            nomaps.value = maps.value.length == 0;
            if (!nomaps.value  && maps.value[0]) {
                mapid.value = maps.value[0].id;
            }
            loaded.value = true;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            showselectmodal.value = false;
        });
    }

    /**
     * Get currently selected map (if any)
     */
    function get_selected() {

        moodleFetch(
            'local_gugrades_get_selected_conversion',
            {
                gradeitemid: 0,
                gradecategoryid: props.categoryid,
            }
        )
        .then((result: any) => {

            // id==0 if no selection (which is fine).
            selection.value = result.id;
            mapname.value = result.name;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            showselectmodal.value = false;
        });
    }

    /**
     * Conversion button has been clicked
     */
    function conversion_clicked() {
        get_maps();
        get_selected();
        showselectmodal.value = true;
    }

    /**
     * Save button has been clicked
     */
    function save_clicked() {

        waiting.value = true;

        moodleFetch(
            'local_gugrades_select_conversion',
            {
                gradeitemid: 0,
                gradecategoryid: props.categoryid,
                mapid: mapid.value,
            }
        )
        .then(() => {
            waiting.value = false;
            toast.success('Map selection saved');
            showselectmodal.value = false;
            emits('converted');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            showselectmodal.value = false;
        });
    }

    /**
     * Remove button has been clicked
     *
     */
    function remove_clicked() {
        waiting.value = true;

        moodleFetch(
            'local_gugrades_select_conversion',
            {
                gradeitemid: 0,
                gradecategoryid: props.categoryid,
                mapid: 0,
            }
        )
        .then(() => {
            waiting.value = false;
            toast.success('Map selection removed');
            showselectmodal.value = false;
            emits('converted');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            showselectmodal.value = false;
        });
    }
</script>