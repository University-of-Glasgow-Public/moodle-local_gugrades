<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="border rounded-md p-2 mt-2 mb-2 border-gray-300">
        <TwButton color="primary" @click="filterButtonClick">
            <span>{{ mstrings['filterbtn'] }}</span>
        </TwButton>
    </div>

    <VueModal v-model="showfiltermodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings['filtertitle']">
        <NameFilter 
            @firstinitialselected="firstInitialSelected" 
            @lastinitialselected="lastInitialSelected" 
            :resetfilter="resetfilter"
            :firstinitial="firstinitial"
            :lastinitial="lastinitial"
        ></NameFilter>

        <div class="columns-4">
            <FormKit
                type="select"
                name="whichcolumn"
                id="whichcolumn"
                :label="mstrings['column']"
                :placeholder="mstrings['selectcolumn']"
                :options="filterheaders"
            />
            <FormKit
                type="select"
                name="conditions"
                id="conditions"
                :label="mstrings['selectconditions']"
                :placeholder="mstrings['selectconditions']"
                :options="conditions"
            />
            <FormKit
                v-if="usescale"
                id="columnvalues"
                type="select"
                outer-class="mb-3"
                :label="mstrings.grade"
                :placeholder="mstrings.specifyscale"
                name="scale"
                v-model="scale"
                :options="scalemenu"
            ></FormKit>
            <FormKit
                v-if="!usescale"
                id="columnvalues"
                type="text"
                outer-class="mb-3"
                :label="mstrings.grade"
                :placeholder="mstrings['selectgrade']"
                number="float"
                name="grade"
                v-model="grade"
            ></FormKit>
            <FormKit type="button" ignore="false" suffixIcon="add"></FormKit>
        </div>

        <div class="divide-gray-300"></div>

        <div class="mt-2 pt-2">
            <TwButton color="secondary" @click="resetFilter()">{{ mstrings['filterreset'] }}</TwButton>
            <TwButton color="primary" @click="applyFilter()">{{ mstrings['filterapply'] }}</TwButton>
            <TwButton color="warning" @click="showfiltermodal = false">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>

</template>

<script setup lang="ts">
    import { ref, provide } from 'vue';
    import { reset } from '@formkit/vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import NameFilter from '@/components/Common/NameFilter.vue';
    import { storeToRefs } from 'pinia';
    import TwButton from '../Tailwind/TwButton.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { IMenuItem, IGradetype } from '@/js/Interfaces';
    import { moodleFetch } from '@/js/moodlefetch';

    interface IScaleOption {
        value: string;
        label: string;
    }

    const debug = ref({});
    const firstinitial = ref('all');
    const lastinitial = ref('all');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const showfiltermodal = ref(false);
    const resetfilter = ref(false);
    const conditions = ref< IMenuItem[]>([]);
    const grade = ref(0);
    const gradetypes = ref< IGradetype[] >([]);
    const scale = ref< string >('');
    const scalemenu = ref< IScaleOption[] >([]);
    const usescale = ref(false);
    provide('resetfilter', resetfilter);
    const emit = defineEmits(['applyfilter']);

    const props = defineProps<{
        filterheaders: Array<0>,
        itemid: Number,
    }>();

    conditions.value = [
        { value: '>', label: mstringstore.getMstring('filtergreaterthan')},
        { value: '>=', label: mstringstore.getMstring('filtergreaterthanorequalto')},
        { value: '<', label: mstringstore.getMstring('filterlessthan')},
        { value: '<=', label: mstringstore.getMstring('filterlessthanorequalto')},
        { value: '=', label: mstringstore.getMstring('filterisequalto')},
        { value: '!=', label: mstringstore.getMstring('filterisnotequalto')},
    ];

    /**
     * Get the grades that can be selected.
     */
    function get_gradeoptions() {
        moodleFetch(
            'local_gugrades_get_add_grade_form',
            {
                gradeitemid: props.itemid,
                userid: 0,
            }
        )
        .then((result: any) => {
            gradetypes.value = result.gradetypes;
            usescale.value = result.usescale;
            scalemenu.value = result.scalemenu;
            //adminmenu.value = result.adminmenu;

            // Add 'use grade' option onto front of adminmenu
            // adminmenu.value.unshift({
            //     value: 'GRADE',
            //     label: mstringstore.getMstring('selectnormalgrade'),
            // });
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Process letter selected in one of the bars
     */
    function firstInitialSelected(letter: string) {
        firstinitial.value = letter;
    }

    function lastInitialSelected(letter: string) {
        lastinitial.value = letter;
    }

    /**
     * When the Filter button is clicked, display the modal.
     */
    function filterButtonClick() {
        showfiltermodal.value = true;
        get_gradeoptions();
    }

    /**
     * Reset the filters.
     */
    function resetFilter() {
        firstinitial.value = 'all';
        lastinitial.value = 'all';
        resetfilter.value = true;
        reset('whichcolumn', '');
        reset('conditions', '');
        reset('columnvalues', '');
    }

    /**
     * Apply the selected filters
     */
    function applyFilter() {
        emit('applyfilter', firstinitial.value, lastinitial.value);
        showfiltermodal.value = false;
    }
</script>