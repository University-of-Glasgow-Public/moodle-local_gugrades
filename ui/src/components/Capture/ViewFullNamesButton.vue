<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <button @click="toggle_view" class="btn btn-outline btn-secondary mr-2 btn-sm">
        <Binoculars :size="18" />
        <span v-if="!togglereveal">{{ mstrings.viewfullnames }}</span>
        <span v-if="togglereveal">{{ mstrings.hidefullnames }}</span>
    </button>
</template>

<script setup lang="ts">
    import {ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useMstrings } from '@/stores/mstrings.js';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { Binoculars } from '@lucide/vue';

    const hascapability = ref(false);
    const togglereveal = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits(['viewfullnames']);

    /**
     * Export data to file
     */
    function toggle_view() {
        togglereveal.value = !togglereveal.value;
        emit('viewfullnames', togglereveal.value);
    }

    /**
     * Check capability
     */
    onMounted(() => {
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:viewhiddennames'
            }
        )
        .then((result: any) => {
            hascapability.value = result['hascapability'];
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });

    });

</script>