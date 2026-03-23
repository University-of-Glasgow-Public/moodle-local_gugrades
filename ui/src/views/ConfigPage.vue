<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="tw:mt-5">
        <LevelOneSelect  @levelchange="levelOneChange"></LevelOneSelect>
    </div>

    <div class="divider"></div>

    <ConfigError v-if="treeerror" :errormessage="treeerror"></ConfigError>

    <div v-if="showresitoption && caneditgrades" class="border rounded p-2 mt-2">
        <button v-if="!configuringresits" type="button" class="btn btn-outline-primary" @click="click_configure">{{ mstrings['configurereassessments'] }}</button>
        <div v-else>
            <div class="alert alert-primary" v-html="mstrings['resit_help']"></div>
            <button type="button" class="btn btn-outline-success" @click="click_finish">{{ mstrings['finish'] }}</button>
        </div>
    </div>

    <div v-if="loaded && !treeerror">
        <h3>{{ categoryname }}</h3>
        <table class="tw:table tw:border-none">
            <ConfigTree :nodes="activitytree" :depth="1" :resitconfig="configuringresits" :resitfade="true" @saveerror="handle_saveerror"></ConfigTree>
        </table>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import LevelOneSelect from '@/components/LevelOneSelect.vue';
    import ConfigTree from '@/components/ConfigTree.vue';
    import ConfigError from '@/components/ConfigError.vue';
    import PleaseWait from '@/components/PleaseWait.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import { usePopulateTrees } from '../js/setuptrees.js';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    const categoryid = ref(0);
    const activitytree = ref();
    const categoryname = ref('');
    const loaded = ref(false);
    const showresitoption = ref(false);
    const configuringresits = ref(false);
    const caneditgrades = ref(false);
    const debug = ref({});
    const treeerror = ref('');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * onMounted, get write grades capability
     */
    onMounted(() => {

        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:editgrades'
            }
        )
        .then((result) => {
            caneditgrades.value = result.hascapability;
        })
        .catch((error) => {
            window.console.log(error);
            debug.value = error;
        });
    });

    /**
     * Deal with save error down in tree structure
     */
    function handle_saveerror(error) {
        debug.value = error;
    }

    /**
     * Capture change to top level category dropdown
     * @param {*} level
     */
    function levelOneChange(level) {
        categoryid.value = parseInt(level);
        if (categoryid.value) {
            getActivities(categoryid.value);
        }
    }

    /**
     * Configure resit button clicked
     */
    function click_configure() {
        configuringresits.value = true;
    }

    /**
     * Configuring resits finished. Mostly we run recalculate.
     * We don't need to wait for this finishing as it does not
     * affect this screen at all.
     */
    function click_finish() {
        moodleFetch(
            'local_gugrades_recalculate',
            {
                gradecategoryid: categoryid.value,
            }
        )
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });
        configuringresits.value = false;

        const populatetrees = usePopulateTrees();
        populatetrees.populate();
    }

    /**
     * Get tree structure of activities and grade categories
     */
    function getActivities(catid) {
        const treestore = useActivityTreeStore();
        const tree = JSON.parse(treestore.trees[catid]);
        treeerror.value = treestore.errors[catid];

        if (!treeerror.value) {
            activitytree.value = tree;
            categoryname.value = tree.category.fullname;
        }
        showresitoption.value = tree.anyresitcandidates;
        loaded.value = true;
    }
</script>