<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwAlert class="tw:mb-2">{{  mstrings.examplevalues }}</TwAlert>

    <FormKit v-if="loaded" type="form" submit-label="Save" :disabled="!caneditgrades" @submit="submit_form">
        <FormKit
            type="text"
            outer-class="mb-3"
            :label="mstrings.conversionmapname"
            :actions="ordervalidated"
            :disabled="!caneditgrades"
            validation-visibility="live"
            validation="required"
            name="mapname"
            v-model="mapname"
        ></FormKit>
        <FormKit
            type="submit"
            label="Save"
        />
        <FormKit
            type="text"
            outer-class="mb-3"
            :label="mstrings.maxgrade"
            :disabled="!caneditgrades"
            number="float"
            validation="required|between:0,200"
            validation-visibility="live"
            name="maxgrade"
            v-model="maxgrade"
        ></FormKit>
        <FormKit
            type="select"
            :label="mstrings.scaletype"
            :disabled="(props.mapid != 0) || !caneditgrades"
            name="scaletype"
            v-model="scaletype"
            value="schedulea"
            :options="scaletypeoptions"
        ></FormKit>
        <div class="mt-3"></div>
        <FormKit
            :label="mstrings.entrytype"
            v-model="entrytype"
            type="radio"
            :options="entrytypeoptions"
            :disabled="!caneditgrades"
        ></FormKit>
        <div class="row mt-3">
            <div class="col-2"><h3>{{ mstrings.band }}</h3></div>
            <div class="col-5"><h3>{{ mstrings.percentage}}</h3></div>
            <div class="col-5"><h3>{{ mstrings.points }}</h3></div>
        </div>

        <div  class="row" v-for="item in items" :key="item.band">
            <div class="col-2 pt-2">
                <h3>{{  item.band  }}</h3>
            </div>
            <div class="col-5">
                <FormKit
                    type="text"
                    number="float"
                    outer-class="mb-3"
                    :disabled="(entrytype != 'percentage') || (item.band == 'H') || !caneditgrades"
                    validation="between:0,100"
                    validation-visibility="blur"
                    :validation-messages="{
                        between: 'Percentage must be between 0 and 100',
                        validate_order: 'Values must be in ascending sequence',
                    }"
                    :model-value="item.boundpc?.toString() ?? ''"
                    @input="(event) => handleInput(item, event)"
                ></FormKit>
            </div>
            <div class="col-5">
                <FormKit
                    type="text"
                    number="float"
                    outer-class="mb-3"
                    :disabled="(entrytype != 'points') || (item.band == 'H') || !caneditgrades"
                    :validation-rules="{ validate_points }"
                    validation="validate_points|validate_order"
                    validation-visibility="blur"
                    :validation-messages="{
                        validate_points: 'Number must be between 0 and ' + maxgrade,
                        validate_order: 'Values must be in ascending sequence',
                    }"
                    :model-value="item.boundpoints?.toString() ?? ''"
                    @input="(event) => handleInput(item, event)"
                ></FormKit>
            </div>
        </div>

        <div v-if="!ordervalidated" class="alert alert-danger my-3">
            {{ mstrings.mapnotinorder }}
        </div>
    </FormKit>

    <div class="tw:flex tw:justify-end">
        <TwButton color="warning" @click="cancel_button">{{ mstrings.cancel }}</TwButton>
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
    import TwButton from '../Tailwind/TwButton.vue';
import TwAlert from '../Tailwind/TwAlert.vue';

    interface IBandItem {
        band: string;
        boundpc: number | null;
        boundpoints: number | null;
        grade: number;
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
        {value: 'schedulea', label: 'Schedule A'},
        {value: 'scheduleb', label: 'Schedule B'},
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

    /**
     * Round values to 5 decimal place
     * TODO: This might change
     */
    function precision(num: number, decimals: number) {
        return parseFloat(num.toFixed(decimals));
    }

    // Method to handle input changes and convert string to number
    const handleInput = (item: IBandItem, newValue: string | number | undefined) => {
        if (typeof newValue === 'string') {
            item.boundpc = parseFloat(newValue) || 0; // Convert to number, default to 0 if invalid
        } else if (typeof newValue === 'number') {
            item.boundpc = newValue; // Use the number directly
        } else {
            item.boundpc = 0; // Fallback to 0 if newValue is undefined
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
     * Custom rule for points values
     */
    function validate_points(node: FormKitNode) {

        // Careful about text fields not being treated as numbers properly.
        const points = Number(node.value);
        const validated = (points >= 0) && (points <= maxgrade.value);

        return validated;
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