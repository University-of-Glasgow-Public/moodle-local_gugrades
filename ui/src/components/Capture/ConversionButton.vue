<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <MenuButton @click="conversion_clicked" iconName="Table">
        {{ mstrings.convertgrades }}
    </MenuButton>

    <VueModal v-model="showselectmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings['conversionselect']">

        <PleaseWait v-if="waiting"></PleaseWait>

        <div v-if="showmismatch">
            <UAlert class="mb-3">{{ mstrings['conversionmismatch'] }}</UAlert>
            <div class="inline-flex gap-2 mt-2">
                <UButton variant="primary"  @click="save_clicked" :disabled="mapid == 0">{{ mstrings['yes'] }}</UButton>
                <UButton variant="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</UButton>
            </div>
        </div>


        <div v-if="!showmismatch">

            <!-- Show the selected map name (if there is one)-->
            <p v-if="mapname" class="mb-2">
                {{ mstrings['selectedmap'] }}: <b>{{ mapname }}</b>
            </p>

            <!--  If no map is currently selected, show the selection dialogue -->
            <div v-if="!selection">

                <!-- if there are no grades then don't try to convert -->
                <UAlert v-if="!anygrades">
                    {{ mstrings['nogradestoconvert'] }}
                    <UButton @click="showselectmodal = false">{{ mstrings['cancel'] }}</UButton>
                </UAlert>

                <div v-if="anygrades">
                    <UAlert v-if="nomaps && loaded">{{ mstrings['nomaps'] }}</UAlert>
                    <UAlert v-else>{{ mstrings['noimportafterconversion'] }}</UAlert>

                    <table v-if="!nomaps && loaded" class="my-4 w-full text-left border-collapse text-sm">
                        <thead class="bg-university-blue text-white uppercase text-xs tracking-wider">
                            <tr>
                                <th v-for="header in headers" :key="header.value" class="px-4 py-2 font-semibold">
                                    {{ header.text }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-light-purple/20">
                            <tr v-for="item in maps" :key="item.id" class="hover:bg-brand-light-purple/10 transition-colors">
                                <td v-for="header in headers" :key="header.value" class="px-4 py-2">
                                    <input v-if="header.value === 'select'" 
                                        type="radio" 
                                        :value="item.id" 
                                        v-model="mapid"
                                    />
                                    <template v-else>
                                        {{ (item as any)[header.value] }}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="inline-flex gap-2 mt-2">
                        <UButton variant="primary"  @click="save_clicked" :disabled="mapid == 0">{{ mstrings['save'] }}</UButton>
                        <UButton variant="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</UButton>
                    </div>
                </div>
            </div>

            <!-- if a map is selected then show warning message and option to remove -->
            <div v-if="selection">
                <UAlert class="mb-3">{{ mstrings['conversionremovewarning'] }}</UAlert>
                <div class="inline-flex gap-2 mt-2">
                    <UButton variant="error" @click="remove_clicked">{{ mstrings['remove'] }}</UButton>
                    <UButton variant="warning" @click="showselectmodal = false">{{ mstrings['cancel'] }}</UButton>
                </div>
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
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import MenuButton from '../Common/MenuButton.vue';
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