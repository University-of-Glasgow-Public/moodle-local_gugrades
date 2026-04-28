/**
 * Display drop-drown for top-level
 */

<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div>
        <TwAlert v-if="notsetup">{{ mstrings.notoplevel }}</TwAlert>
        <TwAlert v-if="itemerror">
            {{ mstrings.changedgradetype }}
            <ul>
                <li v-for="item in erroritems">{{ item.itemname }}</li>
            </ul>
        </TwAlert>
        <div class="tw:flex tw:justify-between">
            <select v-if="!notsetup && !itemerror" v-model="categoryid" class="tw:select tw:w-120">
                <option disabled value="0">{{ mstrings.selectgradecategory }}</option>
                <option v-for="category in level1categories" :key="category.id" :value="category.id" :selected="selected == category.id">{{ category.fullname }}</option>
            </select>
            <div class="tw:border-2 tw:rounded tw:px-2 tw:pt-1 tw:border-red-500">
                <p class="tw:text-xs tw:mb-0 tw:text-red-500">Regulations used:</p>
                <p class="tw:mb-0 tw:text-red-500">{{ regulation }}</p>
                <p v-if="regulationextra" class="tw:mb-0 tw:text-green-500 tw:text-xs tw:text-center tw:uppercase">{{ regulationextra }}</p>
            </div>
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
    import TwAlert from '@/components/Tailwind/TwAlert.vue';

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

    const emit = defineEmits(['levelchange']);

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

    watch(categoryid, () => {
        level1store.categoryid = categoryid.value;
        emit('levelchange', categoryid.value);
    })

    onMounted(() => {
        getLevelOne();
        if (selected.value != 0) {
            emit('levelchange', selected.value);
        }
    });
</script>
