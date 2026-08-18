/**
 * Display drop-drown for top-level
 */

<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div>
        <UAlert v-if="notsetup">{{ mstrings.notoplevel }}</UAlert>
        <div>
            <div class="a11y-picker-label text-sm font-bold mb-1 opacity-70">CATEGORY</div>
            <select
                id="level1select"
                v-if="!notsetup && !itemerror" 
                v-model="categoryid" 
                @change="handleUserSelection"
                class="a11y-picker-select appearance-none px-3 py-2 pr-8 rounded-md bg-white text-brand-dark-purple border border-slate-300 w-80 shadow-md focus:outline-none focus:border-university-blue bg-no-repeat"
                style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236b7280%22 stroke-width=%222%22%3E%3Cpath d=%22M6 9l6 6 6-6%22/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-size: 16px;"
                aria-label="Select top-level grade category"
            >
                <option disabled value="0">{{ mstrings.selectgradecategory }}</option>
                <option v-for="category in level1categories" :key="category.id" :value="category.id" :selected="selected == category.id">{{ category.fullname }}</option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, watch} from 'vue';
    import { storeToRefs } from 'pinia';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { ICategories, IErrorItems } from '@/js/Interfaces';
    import { useLeve1Store } from '@/stores/level1';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from './UAlert.vue';

    const level1categories = ref< ICategories[] >([]);
    const categoryid = ref(0);
    const erroritems = ref< IErrorItems[] >([]);
    const selected = ref(0);
    const notsetup = ref(false);
    const itemerror = ref(false);
    const debug = ref({});
    const regulation = ref('');
    const regulationextra = ref('');
    const level1store = useLeve1Store();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits(['levelchange', 'regulation']);

    export interface IRegulationEmit {
        regulationshort: string;
        regulation: string;
        regulationextra: string;
    }

    // Get the top level categories
    function getLevelOne() {
        moodleFetch(
            'local_gugrades_get_levelonecategories',
            {}
        )
        .then((result: any) => {

            level1categories.value = result.categories;
            erroritems.value = result.erroritems;
            regulation.value = result.regulation;
            regulationextra.value = result.regulationextra;
            notsetup.value = level1categories.value.length == 0;
            itemerror.value = erroritems.value.length > 0;

            // Emit regulation info to parent component
            emit('regulation', {
                regulationshort: result.regulationshort,
                regulation: result.regulation,
                regulationextra: result.regulationextra
            });

            // If it's already been selected on another tab...
            selected.value = level1store.getvalidcategoryid(level1categories.value);
            if (selected.value) {
                categoryid.value = selected.value;
                emit('levelchange', selected.value);
            }

            // if there's only one then might as well select it.
            if ((level1categories.value.length == 1) && (0 in level1categories.value) && !itemerror.value && !notsetup.value) {
                selected.value = level1categories.value[0].id;
                categoryid.value = selected.value;
                emit('levelchange', selected.value);
            }
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    }

    /**
    watch(categoryid, () => {
        level1store.categoryid = categoryid.value;
        emit('levelchange', categoryid.value);
    })
    */

    function handleUserSelection() {
        level1store.categoryid = categoryid.value;
        emit('levelchange', categoryid.value);
    }

    onMounted(() => {
        itemerror.value = false;
        getLevelOne();
        if (selected.value != 0) {
            emit('levelchange', selected.value);
        }
    });
</script>
