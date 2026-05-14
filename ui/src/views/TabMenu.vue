<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <RegulationCheck></RegulationCheck>

    <PleaseWait v-if="waiting" message="Setting up MyGrades"></PleaseWait>

    <div v-if="!waiting">
        <TWAlert v-if="!available" color="error">
            MyGrades cannot be used in this course as it has too many enrolled participants.
        </TWAlert>
        <div v-else id="tabmenu">
            <TabsNav></TabsNav>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, computed} from 'vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import TabsNav from '@/components/TabsNav.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import RegulationCheck from '@/components/RegulationCheck.vue';

    const currenttab = ref('capture');
    const level1category = ref(0);
    const showactivityselect = ref(false);
    const itemid = ref(0);
    const available = ref(true);
    const debug = ref({});

    /**
     * Anything in here that involves MyGrades waiting to open
     */
    const waiting = computed(() => {
        const treestore = useActivityTreeStore();

        return !treestore.ready;
    })

    /**
     * Check for aggregation tab permission
     */
     onMounted(() => {

        // Check that MyGrades is available for this course at all.
        moodleFetch(
            'local_gugrades_is_mygrades_available',
            {}
        )
        .then((result: any) => {
            available.value = result.available;
        })
        .catch((error) => {
            window.console.log(error);
            debug.value = error;
        });
    })
</script>