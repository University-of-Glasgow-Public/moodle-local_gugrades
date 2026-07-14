<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <VueModal v-model="showModal" :enableClose="false" modalClass="rounded max-w-3xl" title="Setup and checking">
        <UAlert v-if="showerrors">
            <div class="mb-4">
                A data integrity check has found invalid data in MyGrades. This is probably due to changing the course start
                date or manipulating Gradebook settings AFTER grades have already been imported. MyGrades cannot continue.
            </div>
            <div v-for="error in errors">
                <b>{{ error.itemname }}</b> {{ error.error }}
            </div>
        </UAlert>
        <UAlert v-else>
            Setting up MyGrades and checking data integrity.
        </UAlert>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref, onMounted, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from './Common/UAlert.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import { usePopulateTrees } from '@/js/setuptrees.ts';

    interface iError {
        gradeitemid: number;
        itemname: string;
        error: string;
    }

    const treestore = useActivityTreeStore();
    const populatetrees = usePopulateTrees();
    const { ready: treeReady } = storeToRefs(treestore);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs(mstringstore);
    
    const debug = ref({});
    const errors = ref<iError[]>([]);
    const showerrors = ref(false);
    
    // We replace the confusing 'processing' and 'waiting' flags 
    // with one single status that tells us if the setup is running.
    const isSettingUp = ref(true);

    // Completely simplified visibility tracking
    const showModal = computed({
        get() {
            // Keep modal open if we are setting up OR if we have data errors to display
            return isSettingUp.value || showerrors.value;
        },
        set(value) {
            if (!value) {
                isSettingUp.value = false;
            }
        }
    });

    onMounted(async () => {
        try {
            // Step 1: Force tree store ready to false immediately on mount
            treestore.ready = false; 

            // Step 2: Fetch your data integrity check from the server
            const result: any = await moodleFetch('local_gugrades_check_integrity', {});
            errors.value = result.erroritems;

            // Step 3: If there are errors, stop immediately and show them
            if (errors.value.length > 0) {
                console.log(errors.value);
                showerrors.value = true;
                return; // Stops execution here so modal stays open with errors
            }

            // Step 4: No errors! Now kick off and populate your activity trees
            // We use the new async composable here
            await populatetrees.populate();

            // Step 5: Everything is 100% finished. Now, and ONLY now, close the modal.
            isSettingUp.value = false;

        } catch (error: any) {
            debug.value = error;
            console.error(error);
        }
    });
</script>
