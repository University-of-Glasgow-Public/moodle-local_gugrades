<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="tw:mt-2 tw:border-solid tw:border-2 tw:border-gray-500 tw:p-3 tw:rounded-md" v-if="loaded">
        <div v-if="collapsed" @click="open_selection" class="cursor-pointer row">
            <div v-if="selectedactivity" class="col-10">
                {{ mstrings.selected }}: {{ selectedactivity.itemname }}
            </div>
            <div class="col-2 text-right">
                <i class="fa fa-chevron-down" aria-hidden="true"></i>
            </div>
        </div>
        <div v-else>
            <b>{{ categoryname }}</b>
            <ActivityTree v-if="!treeerror" :nodes="activitytree" @activityselected="activity_selected" :depth="1"></ActivityTree>
            <ConfigError v-if="treeerror" :errormessage="treeerror"></ConfigError>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, watch } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ActivityTree from '@/components/ActivityTree.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import ConfigError from '@/components/ConfigError.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import type { IGradeitem } from '@/js/Interfaces.js';

    const props = defineProps({
        categoryid: {
            type: Number,
            required: true,
        },
        currentitem: Number,
    });

    const emit = defineEmits(['activityselected']);

    const activitytree = ref({});
    const categoryname = ref('');
    const selectedactivity = ref< IGradeitem | undefined >(undefined);
    const loaded = ref(false);
    const collapsed = ref(false);
    const treeerror = ref('');
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    // Get the sub-category / activity
    function getActivity() {
        const treestore = useActivityTreeStore();

        const catid = props.categoryid;
        if (treestore.trees[catid]) {
            const tree = JSON.parse(treestore.trees[catid]);
            if (treestore.errors[catid]) {
                treeerror.value = treestore.errors[catid];
            } else {
                treeerror.value = '';
            }
            if (!treeerror.value) {
                activitytree.value = tree;
                categoryname.value = tree.category.fullname;
            } else {
                activitytree.value = [];
                categoryname.value = '';
            }
            loaded.value = true;
        }
    }

    // Get the sub-category / activity
    function getActivityOld() {
        const catid = props.categoryid;

        moodleFetch(
            'local_gugrades_get_activities',
            {
                categoryid: catid
            }
        )
        .then((result: any) => {
            const tree = JSON.parse(result['activities']);
            treeerror.value = result.error;

            if (!treeerror.value) {
                activitytree.value = tree;
                categoryname.value = tree.category.fullname;
            } else {
                activitytree.value = [];
                categoryname.value = '';
            }
            loaded.value = true;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    }

    // Get the selected avtivity
    function activity_selected(activityid: number) {
        moodleFetch(
            'local_gugrades_get_grade_item',
            {
                itemid: activityid,
            }
        )
        .then((result: any) => {
            selectedactivity.value = result;
            collapsed.value = true;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });

        // Emit id as well
        emit('activityselected', activityid);
    }

    // (Re-)open the selection
    function open_selection() {
        collapsed.value = false;
    }

    /**
     * onMounted
     */
    onMounted(() => {
        getActivity();

        // Could be mounted with something selected
        if (props.currentitem) {
            activity_selected(props.currentitem);
            collapsed.value = true;
        }
    });

    /**
     * If the categoryid prop changes then we read new values
     * and (re-)open the dialogue
     */
    watch(() => props.categoryid, () => {
        collapsed.value = false;
        getActivity();
    })
</script>

<style scoped>
    .cursor-pointer {
        cursor: pointer;
    }
</style>