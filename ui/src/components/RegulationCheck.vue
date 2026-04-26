<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <VueModal v-model="admingradesinvalid" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" title="Issue with configuration">
        <TwAlert>{{ mstrings.admingradesinvalid }}</TwAlert>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const datemismatch = ref(false);
    const admingradesinvalid = ref(true);
    const debug = ref({});

    onMounted(() => {
        moodleFetch(
            'local_gugrades_regulation_check',
            {}
        )
        .then((result: any) => {
            datemismatch.value = result.datemismatch;
            admingradesinvalid.value = !result.admingradesvalid;
        })
        .catch(error => {
            debug.value = error;
            console.error(error);
        });
    })
</script>
