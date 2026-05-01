<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="toggle_view">
        <span v-if="!togglereveal">{{ mstrings.viewfullnames }}</span>
        <span v-if="togglereveal">{{ mstrings.hidefullnames }}</span>
    </TwButton>
</template>

<script setup lang="ts">
    import {ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useMstrings } from '@/stores/mstrings.js';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwButton from '../Tailwind/TwButton.vue';

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