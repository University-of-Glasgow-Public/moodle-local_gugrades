<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UButton @click="toggle_view" variant="secondary" appearance="outline" size="sm" class="mr-2">
        <Binoculars :size="18" />
        <span v-if="!props.revealnames">{{ mstrings.viewfullnames }}</span>
        <span v-if="props.revealnames">{{ mstrings.hidefullnames }}</span>
    </UButton>
</template>

<script setup lang="ts">
    import {ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useMstrings } from '@/stores/mstrings.js';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { Binoculars } from '@lucide/vue';
    import UButton from '../Common/UButton.vue';

    const hascapability = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        revealnames: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits(['viewfullnames']);

    /**
     * Toggle revealing student names for anonymous assignments.
     */
    function toggle_view() {
        emit('viewfullnames', !props.revealnames);
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