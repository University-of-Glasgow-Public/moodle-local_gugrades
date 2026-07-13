<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <SetupAndCheck />

    <TWAlert v-if="!available" color="error">
        MyGrades cannot be used in this course as it has too many enrolled participants.
    </TWAlert>
    <div v-else id="tabmenu">
        <TabsNav></TabsNav>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, computed} from 'vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import TabsNav from '@/components/TabsNav.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import SetupAndCheck from '@/components/SetupAndCheck.vue';

    const available = ref(true);
    const debug = ref({});

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
            console.error(error);
            debug.value = error;
        });
    })
</script>