<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="bg-base-100 border border-base-300 rounded-md mt-4 p-6">
        <FormKit v-if="loaded" type="form" submit-label="Save" :actions="caneditgrades" :disabled="!caneditgrades" @submit="submit_form">

            <div class="flex gap-2">

                <!-- Map name -->
                <FormKit
                    type="text"
                    outer-class="w-60"
                    :label="mstrings.conversionmapname"
                    :actions="ordervalidated"
                    :disabled="!caneditgrades"
                    validation-visibility="live"
                    validation="required"
                    name="mapname"
                    v-model="mapname"
                ></FormKit>

                <!-- Max point grade -->
                <FormKit
                    type="text"
                    outer-class="w-60"
                    :label="mstrings.maxgrade"
                    :disabled="!caneditgrades || (entrytype == 'percentage')"
                    number="float"
                    validation="required|between:0,200"
                    validation-visibility="live"
                    name="maxgrade"
                    v-model="maxgrade"
                ></FormKit>

                <!-- Scale type -->
                <FormKit
                    type="select"
                    outer-class="w-60"
                    :label="mstrings.scaletype"
                    :disabled="(props.mapid != 0) || !caneditgrades"
                    name="scaletype"
                    v-model="scaletype"
                    value="schedulea"
                    :options="scaletypeoptions"
                ></FormKit>

                <HelpButton class="mt-11" title="Help with editing maps" subject="editmap"/>

            </div>

            <div class="mt-3"></div>

            <FormKit
                class="mb-4"
                :label="mstrings.entrytype"
                v-model="entrytype"
                type="radio"
                :options="entrytypeoptions"
                :disabled="!caneditgrades"
                options-class="flex flex-row gap-4 list-none p-0 m-0"
                option-class="flex items-center"
                input-class="appearance-none w-4 h-4 rounded-full border-2 border-solid border-gray-400 mr-2 shrink-0 cursor-pointer checked:border-gray-800 checked:bg-[radial-gradient(circle,_#2C2C2A_40%,_transparent_41%)]"
                label-class="text-[13px] text-gray-400 cursor-pointer"
            />

            <div class="divider"></div>

            <div :class="isScheduleA ? 'grid grid-cols-2 gap-x-6' : 'flex flex-col'">

                <!-- Left column -->
                <div>
                    <div class="flex">
                        <div class="w-24 font-bold"><h3>{{ mstrings.band }}</h3></div>
                        <div class="w-60 mr-5 font-bold"><h3>{{ mstrings.percentage }}</h3></div>
                        <div class="w-60 font-bold"><h3>{{ mstrings.points }}</h3></div>
                    </div>
                    <div  class="flex" v-for="item in leftItems" :key="item.band">
                        <div class="pt-2">
                            <h3 class="w-24 flex items-center">
                                <GradeColor :grade="item.band" class="w-10 text-center">{{  item.band  }}</GradeColor>
                            </h3>
                        </div>
                        <div class="w-60 mr-5">
                            <FormKit
                                type="text"
                                number="float"
                                outer-class="mb-3"
                                :disabled="(entrytype != 'percentage') || (item.band == 'H') || !caneditgrades"
                                validation="between:0,100"
                                validation-visibility="blur"
                                :validation-messages="{
                                    between: 'Percentage must be between 0 and 100',
                                }"
                                :model-value="item.boundpc?.toString() ?? ''"
                                @input="(event) => handleInput(item, event)"
                                :aria-label="item.band + ' ' + mstrings.percentage"
                            ></FormKit>
                        </div>
                        <div class="w-60">
                            <FormKit
                                type="text"
                                number="float"
                                outer-class="mb-3"
                                :disabled="(entrytype != 'points') || (item.band == 'H') || !caneditgrades"
                                :validation-rules="{ validate_points }"
                                validation="validate_points"
                                validation-visibility="blur"
                                :validation-messages="{
                                    validate_points: 'Number must be between 0 and ' + maxgrade,
                                }"
                                :model-value="item.boundpoints?.toString() ?? ''"
                                @input="(event) => handleInput(item, event)"
                                :aria-label="item.band + ' ' + mstrings.points"
                            ></FormKit>
                        </div>
                    </div>
                </div>

                <!-- Right column (Schedule A only) -->
                <div v-if="isScheduleA">
                    <div class="flex">
                        <div class="w-24 font-bold"><h3>{{ mstrings.band }}</h3></div>
                        <div class="w-60 mr-5 font-bold"><h3>{{ mstrings.percentage }}</h3></div>
                        <div class="w-60 font-bold"><h3>{{ mstrings.points }}</h3></div>
                    </div>
                    <div class="flex" v-for="item in rightItems" :key="item.band">
                        <div class="pt-2">
                            <h3 class="w-24">
                                <GradeColor :grade="item.band" class="w-10 text-center">{{  item.band  }}</GradeColor>
                            </h3>
                        </div>
                        <div class="w-60 mr-5">
                            <FormKit
                                type="text"
                                number="float"
                                outer-class="mb-3"
                                :disabled="(entrytype != 'percentage') || (item.band == 'H') || !caneditgrades"
                                validation="between:0,100"
                                validation-visibility="blur"
                                :validation-messages="{
                                    between: 'Percentage must be between 0 and 100',
                                }"
                                :model-value="item.boundpc?.toString() ?? ''"
                                @input="(event) => handleInput(item, event)"
                                :aria-label="item.band + ' ' + mstrings.percentage"
                            ></FormKit>
                        </div>
                        <div class="w-60">
                            <FormKit
                                type="text"
                                number="float"
                                outer-class="mb-3"
                                :disabled="(entrytype != 'points') || (item.band == 'H') || !caneditgrades"
                                :validation-rules="{ validate_points }"
                                validation="validate_points"
                                validation-visibility="blur"
                                :validation-messages="{
                                    validate_points: 'Number must be between 0 and ' + maxgrade,
                                }"
                                :model-value="item.boundpoints?.toString() ?? ''"
                                @input="(event) => handleInput(item, event)"
                                :aria-label="item.band + ' ' + mstrings.points"
                            ></FormKit>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!ordervalidated" class="alert alert-danger my-3">
                {{ mstrings.mapnotinorder }}
            </div>
        </FormKit>

        <div class="flex justify-start mt-2">
            <UButton variant="warning" @click="cancel_button">{{ caneditgrades ? mstrings.cancel : mstrings.return }}</UButton>
        </div>

    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, watch, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import { watchDebounced } from '@vueuse/core';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { IConversionMap } from '@/js/Interfaces';
    import type { FormKitNode } from '@formkit/core';
    import UButton from '../Common/UButton.vue';
    import { gradecolors } from '@/js/GradeColors';
    import HelpButton from '../Common/HelpButton.vue';
    import GradeColor from '../Common/GradeColor.vue';

    interface IBandItem {
        band: string;
        boundpc: number | null;
        boundpoints: number | null;
        grade: number;
        colorclass: string;
    }

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const loaded = ref(false);
    const mapname = ref('');
    const tmpmapname = ref(mapname);
    const maxgrade = ref(100);
    const rawmap = ref< IConversionMap[] >([]);
    const items = ref< IBandItem[] >([]);
    const scaletype = ref('schedulea');
    const entrytype = ref('percentage');
    const scaletypeoptions = [
        {value: 'schedulea', label: 'GGS1'},
        {value: 'scheduleb', label: 'GGS2'},
    ];
    const entrytypeoptions = [
        {value: 'percentage', label: 'Percentage'},
        {value: 'points', label: 'Points'},
    ];
    const debug = ref({});

    const toast = useToast();

    const props = defineProps({
        mapid: Number,
        caneditgrades: Boolean,
    });

    const emits = defineEmits(['close']);

    const isScheduleA = computed(() => scaletype.value === 'schedulea');

    // Split items array into two columns (or just the left one if ScheduleB)
    const leftItems = computed(() =>
        isScheduleA.value ? items.value.slice(0, Math.ceil(items.value.length / 2)) : items.value
    )
    const rightItems = computed(() =>
        isScheduleA.value ? items.value.slice(Math.ceil(items.value.length / 2)) : []
    )

    /**
     * Round values to 5 decimal place
     * TODO: This might change
     */
    function precision(num: number, decimals: number) {
        return parseFloat(num.toFixed(decimals));
    }

    // Method to handle input changes and convert string to number.
    // Write to the field matching the active entry type; recalculate() syncs the other.
    const handleInput = (item: IBandItem, newValue: unknown) => {
        let value = 0;
        if (typeof newValue === 'string') {
            value = parseFloat(newValue) || 0;
        } else if (typeof newValue === 'number') {
            value = newValue;
        }

        if (entrytype.value === 'points') {
            item.boundpoints = value;
        } else {
            item.boundpc = value;
        }
    };

    /**
     * Build items array
     * (depending on scale type)
     */
    function build_items() {
        items.value = [];
        rawmap.value.forEach((item) => {
            items.value.push({
                band: item.band,
                grade: item.grade,
                boundpc: ((item.bound !== 0) ? item.bound : null),
                boundpoints: ((item.bound !== 0) ? precision(item.bound * maxgrade.value / 100, 5) : null),
                colorclass: gradecolors[item.band]!.dot,
            });
        });
    }

    /**
     * Recalculate items.
     * When settings change match percent to point according to
     * entrytypeoptions setting
     */
    function recalculate() {
        // Don't rely on first entry existing
        if (!items.value[0]) {
            return
        }

        // Grade H should always be zero - setting it as such here, prevents the method from messing with the on page value.
        items.value[0].boundpc = 0;
        items.value[0].boundpoints = 0;
        items.value.forEach((item) => {
            if (item.band == 'H') return;
            // If percent selected then recalc points
            if (entrytype.value == 'percentage') {
                item.boundpoints = ((item.boundpc !== null && item.boundpc > 0) ? precision(item.boundpc * maxgrade.value / 100, 5) : null);
            }

            // If points selected then recalc percent
            if (entrytype.value == 'points') {
                item.boundpc = ((item.boundpoints !== null && item.boundpoints > 0) ? precision(item.boundpoints * 100 / maxgrade.value, 5) : null);
            }
        })
    }

    /**
     * If maxgrade changes then we need to recalculate the map
     */
     watchDebounced(
        maxgrade,
        () => {
            build_items();
        },
        { debounce: 500, maxWait: 1000 },
    );

    /**
     * If the schedule changes then the map can be reloaded
     * only if mapid==0. If it's an existing map, then it would
     * need to be deleted and recreated
     */
    watch(
        scaletype,
        () => {
            if (props.mapid == 0) {
                update_map();
            }
        }
    );

    /**
     * Watch the map array for changes to
     */
    watch(
        items,
        () => {
            recalculate();
        },
        {deep: true},
    );

    /**
     * Custom rule for points values.
     * Maximum grade is inclusive so the top band can be set to maxgrade.
     */
    function validate_points(node: FormKitNode) {

        // Careful about text fields not being treated as numbers properly.
        // maxgrade may be a string when bound via FormKit.
        const points = Number(node.value);
        const max = Number(maxgrade.value);
        if (!Number.isFinite(points) || !Number.isFinite(max)) {
            return false;
        }

        return (points >= 0) && (points <= max);
    }

    /**
     * computed to check that points/percentages are in order.
     * H will always 0 - therefore we can skip this,
     */
    const ordervalidated = computed(() => {
        let currentpercent = -1;
        let currentpoints = -1;
        let inorder = true;
        items.value.forEach((item) => {
            if (item.band == 'H') return;

            if (item.boundpc) {
                if (currentpercent >= Number(item.boundpc)) {
                    inorder = false;
                } else {
                    currentpercent = Number(item.boundpc);
                }
            }

            if (item.boundpoints) {
                if (currentpoints >= Number(item.boundpoints)) {
                    inorder = false;
                } else {
                    currentpoints = Number(item.boundpoints);
                }
            }
        });

        return inorder;
    });

    /**
     * Form submitted
     */
    function submit_form() {
        if (!ordervalidated.value) {
            return;
        }

        const map: IConversionMap[] = [];
        items.value.forEach((item) => {
            map.push({
                band: item.band,
                bound: precision(item.boundpc ?? 0, 5),
                grade: item.grade,
            });
        });

        moodleFetch(
            'local_gugrades_write_conversion_map',
            {
                mapid: props.mapid,
                name: mapname.value,
                schedule: scaletype.value,
                maxgrade: maxgrade.value,
                map: map,
            }
        )
        .then(() => {
            toast.success(mstringstore.getMstring('conversionmapsaved'));
            emits('close')
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Cancel button pressed
     */
    function cancel_button() {
        emits('close');
    }

    /**
     * Update the conversion map
     */
    function update_map() {

        moodleFetch(
            'local_gugrades_get_conversion_map',
            {
                mapid: props.mapid,
                schedule: scaletype.value,
            }
        )
        .then((result: any) => {
            mapname.value = ((tmpmapname.value) ? tmpmapname.value : result.name);
            scaletype.value = result.schedule;
            maxgrade.value = result.maxgrade;
            rawmap.value = result.map;

            build_items();

            loaded.value = true;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Is this a new map (id=0) or an existing one
     */
    onMounted(() => {
        update_map();
    })
</script>

<style>
    .lucideFill {
        fill: currentColor;
    }
</style>