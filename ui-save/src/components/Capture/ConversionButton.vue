<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="conversion_clicked">{{ mstrings['convertgrades'] }}</TwButton>

    <VueModal v-model="showselectmodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['conversionselect']">

        <PleaseWait v-if="waiting"></PleaseWait>

        <div v-if="showmismatch">
            <TwAlert class="tw:mb-3">{{ mstrings['conversionmismatch'] }}</TwAlert>
            <TwButton color="primary"  @click="save_clicked" :disabled="mapid == 0">{{ mstrings['yes'] }}</TwButton>
            <TwButton color="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</TwButton>
        </div>


        <div v-if="!showmismatch">

            <!-- Show the selected map name (if there is one)-->
            <p v-if="mapname" class="tw:mb-2">
                {{ mstrings['selectedmap'] }}: <b>{{ mapname }}</b>
            </p>

            <!--  If no map is currently selected, show the selection dialogue -->
            <div v-if="!selection">

                <!-- if there are no grades then don't try to convert -->
                <TwAlert v-if="!anygrades">
                    {{ mstrings['nogradestoconvert'] }}
                    <TwButton @click="showselectmodal = false">{{ mstrings['cancel'] }}</TwButton>
                </TwAlert>

                <div v-if="anygrades">
                    <TwAlert v-if="nomaps && loaded">{{ mstrings['nomaps'] }}</TwAlert>
                    <TwAlert v-else>{{ mstrings['noimportafterconversion'] }}</TwAlert>

                    <EasyDataTable v-if="!nomaps && loaded" :items="maps" :headers="headers" :hide-footer="true" class="tw:my-4">
                        <template #item-select="item">
                            <input type="radio" :value="item.id" v-model="mapid"/>
                        </template>
                    </EasyDataTable>

                    <div>
                        <TwButton color="primary"  @click="save_clicked" :disabled="mapid == 0">{{ mstrings['save'] }}</TwButton>
                        <TwButton color="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</TwButton>
                    </div>
                </div>
            </div>

            <!-- if a map is selected then show warning message and option to remove -->
            <div v-if="selection">
                <TwAlert class="tw:mb-3">{{ mstrings['conversionremovewarning'] }}</TwAlert>
                <TwButton color="danger" @click="remove_clicked">{{ mstrings['remove'] }}</TwButton>
                <TwButton color="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { IMap, IGradeitem } from '@/js/Interfaces';

    const maps = ref<IMap[]>([]);
    const nomaps = ref(true);
    const loaded = ref(false);
    const selection = ref(0);
    const mapid = ref(0);
    const showselectmodal = ref(false);
    const anygrades = ref(false);
    const mapname = ref('');
    const debug = ref({});
    const waiting = ref(false);
    const gradeitem =ref<IGradeitem | null>(null);
    const showmismatch = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const headers = ref([
        {text: mstrings.value['select'], value: 'select'},
        {text: mstrings.value['name'], value: 'name'},
        {text: mstrings.value['scale'], value: 'scale'},
    ]);

    const props = defineProps<{
        itemid: number;
    }>();

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
            if (!nomaps.value) {
                const map = maps.value[0];
                if (map != undefined) {
                    mapid.value = map['id'];
                }
            }
            loaded.value = true;
        })
        .catch((error) => {
            window.console.error(error);
            showselectmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Get currently selected map (if any)
     */
    function get_selected() {
        moodleFetch(
            'local_gugrades_get_selected_conversion',
            {
                gradeitemid: props.itemid,
                gradecategoryid: 0,
            }
        )
        .then((result: any) => {

            // id==0 if no selection (which is fine).
            selection.value = result.id;
            anygrades.value = result.anygrades;
            mapname.value = result.name;
        })
        .catch((error) => {
            window.console.error(error);
            showselectmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Get grade item/
     */
    function get_grade_item() {
        moodleFetch(
            'local_gugrades_get_grade_item',
            {
                itemid: props.itemid,
            }
        )
        .then((result: any) => {
            gradeitem.value = result;
        })
        .catch((error) => {
            window.console.error(error);
            showselectmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Conversion button has been clicked
     */
    function conversion_clicked() {
        get_grade_item();
        get_maps();
        get_selected();
        showselectmodal.value = true;
        showmismatch.value = false;
    }

    /**
     * MGU-1391
     * Check for max grades mismatch
     */
    function is_mismatch() {

        // Get map data
        const map = maps.value.find(m => m.id == mapid.value);

        // Get maximum grade for grade item
        let grademax: number = 0;

        gradeitem.value ? grademax = gradeitem.value.grademax : 0;

        return map ? grademax != map.maxgrade : false;
    }

    /**
     * Save button has been clicked
     */
    function save_clicked() {

        // mismatch
        if (is_mismatch() && !showmismatch.value) {
            showmismatch.value = true;

            return;
        }

        waiting.value = true;

        moodleFetch(
            'local_gugrades_select_conversion',
            {
                gradeitemid: props.itemid,
                gradecategoryid: 0,
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
            showselectmodal.value = false;
            debug.value = error;
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
                gradeitemid: props.itemid,
                gradecategoryid: 0,
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
            showselectmodal.value = false;
            debug.value = error;
        });
    }
</script>