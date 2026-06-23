<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="bg-brand-light-purple/10 border rounded-md mt-2 border-gray-300 shadow-sm">
        <div class="mt-5">
            <LevelOneSelect  @levelchange="levelOneChange" @regulation="getregulation"></LevelOneSelect>
        </div>

        <ConfigError v-if="treeerror" :errormessage="treeerror"></ConfigError>

        <div v-if="(showresitoption || engineering) && caneditgrades &&!newregs" class="my-2">
            <button v-if="!configuringresits" type="button" class="btn btn-accent btn-outline" @click="click_configure">{{ mstrings['configurereassessments'] }}</button>
            <div v-else>
                <div class="alert alert-primary mb-2" v-html="mstrings['resit_help']"></div>
                <button type="button" class="btn btn-success btn-outline" @click="click_finish">{{ mstrings['finish'] }}</button>
            </div>
        </div>
    </div>

    <!-- NEW regulations -->
    <template v-if="loaded && !treeerror && newregs &&activitytree">
        <CategoryConfig :categoryid="categoryid" :nodes="activitytree" :engineering="engineering"></CategoryConfig>
    </template>

    <!-- OLD regulations -->
    <div v-if="loaded && !treeerror && !newregs">
        <table id="config_table" class="table table-zebra mt-4 border rounded-md bg-base-100 border-gray-300 shadow-sm">
            <tbody>
                <ConfigTree
                    v-if="activitytree"
                    :nodes="activitytree"
                    :depth="1"
                    :resitconfig="configuringresits"
                    :resitfade="true"
                    :engineering="engineering"
                    @saveerror="handle_saveerror"
                ></ConfigTree>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import LevelOneSelect, { type IRegulationEmit } from '@/components/Common/LevelOneSelect.vue';
    import ConfigTree from '@/components/Configure/ConfigTree.vue';
    import ConfigError from '@/components/ConfigError.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import { usePopulateTrees } from '../js/setuptrees.js';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import CategoryConfig from '@/components/Configure/CategoryConfig.vue';
    import type { ICategoryCategory } from '@/js/Interfaces.js';

    const categoryid = ref(0);
    const activitytree = ref<ICategoryCategory>();
    const categoryname = ref('');
    const loaded = ref(false);
    const showresitoption = ref(false);
    const configuringresits = ref(false);
    const caneditgrades = ref(false);
    const engineering = ref(false);
    const newregs = ref(false);
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
        .then((result: any) => {
            caneditgrades.value = result.hascapability;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });
    });

    /**
     * Get regulationextra emmited from Level1Select
     */
    function getregulation(regulation: IRegulationEmit) {
        newregs.value = regulation.regulationshort == 'from2026';
        engineering.value = regulation.regulationextra == 'Engineering';
    }

    /**
     * Deal with save error down in tree structure
     */
    function handle_saveerror(error: object) {
        debug.value = error;
    }

    /**
     * Capture change to top level category dropdown
     * @param {*} level
     */
    function levelOneChange(level: number) {
        categoryid.value = level;
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
    function getActivities(catid: number) {
        const treestore = useActivityTreeStore();
            if (treestore.errors[catid]) {
            treeerror.value = treestore.errors[catid];
        }
        if (treestore.trees[catid]) {
            const tree = JSON.parse(treestore.trees[catid]);
            if (!treeerror.value) {
                activitytree.value = tree;
                categoryname.value = tree.category.fullname;
            }
            showresitoption.value = tree.anyresitcandidates;
        }

        loaded.value = true;
    }
</script>
