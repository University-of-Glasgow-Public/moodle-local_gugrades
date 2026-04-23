<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="tw:border tw:rounded-md tw:p-2 tw:mt-2 tw:mb-2 tw:border-gray-300">
        <TwButton color="primary" @click="filterButtonClick">
            <span>{{ mstrings['filterbtn'] }}</span>
        </TwButton>
    </div>

    <VueModal v-model="showfiltermodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['filtertitle']">
        <FormKit type="form">
            <NameFilter @selected="filter_selected"></NameFilter>

            <FormKit
                type="select"
                :label="mstrings['column']"
                name="column"
                :options="headers"
                :placeholder="mstrings['selectcolumn']"
                validation="required"
            />
        </FormKit>

        <div class="tw:divider"></div>

        <div class="tw:mt-2 tw:pt-2">
            <TwButton color="secondary" @click="resetFilter()">{{ mstrings['filterreset'] }}</TwButton>
            <TwButton color="primary" @click="applyFilter()">{{ mstrings['filterapply'] }}</TwButton>
            <TwButton color="warning" @click="showfiltermodal = false">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>

</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import NameFilter from '@/components/Common/NameFilter.vue';
    import { storeToRefs } from 'pinia';
    import TwButton from '../Tailwind/TwButton.vue';
    import { useMstrings } from '@/stores/mstrings.js';

    const debug = ref({});
    const usershidden = ref(false);
    const firstname = ref('');
    const lastname = ref('');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const showfiltermodal = ref(false);

    const props = defineProps({
        headers: Object,
    });

    console.log("headers:", props.headers);

    /**
     * Firstname/lastname filter selected
     * @param {*} first
     * @param {*} last
     */
    function filter_selected(first: string, last: string) {
        if (first == 'all') {
            first = '';
        }
        if (last == 'all') {
            last = '';
        }
        firstname.value = first;
        lastname.value = last;
    }

    /**
     * When button clicked,
     * Apply the selected filters.
     */
    function filterButtonClick() {
        showfiltermodal.value = true;
    }

    /**
     * Reset the filters
     */
    function resetFilter() {

    }

    /**
     * Apply the selected filters
     */
    function applyFilter() {

    }
</script>