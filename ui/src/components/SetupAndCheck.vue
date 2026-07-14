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
    import {ref, onMounted, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from './Common/UAlert.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';

    const treestore = useActivityTreeStore();
    const { ready: treeReady } = storeToRefs(treestore);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const debug = ref({});
    const processing = ref(true);
    const errors = ref([]);
    const showerrors = ref(false);

    /**
     * Anything in here that involves MyGrades waiting to open
     */
    const waiting = computed(() => {
        return !treeReady.value;
    })

    // Combines both states to control the modal visibility
    const showModal = computed({
        get() {
            // Show modal if backend is processing OR store is still waiting
            // EXCEPT if there are integrity errors (we must keep it open to show errors)
            return processing.value || waiting.value || showerrors.value;
        },
        set(value) {
            // Handles internal close events from the modal component
            if (!value) {
                processing.value = false;
            }
        }
    });

    onMounted(() => {
        moodleFetch(
            'local_gugrades_check_integrity',
            {}
        )
        .then((result: any) => {
            errors.value = result.erroritems;
            if (errors.value.length == 0) {
                processing.value = false;
            } else {
                console.log(errors.value);
                showerrors.value = true;
            }
        })
        .catch(error => {
            debug.value = error;
            console.error(error);
        });
    });
</script>
