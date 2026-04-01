<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a @click.prevent="import_grade()">{{ mstrings.importusergrade }}</a>
</template>

<script setup lang="ts">
    import { ref } from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/DebugDisplay.vue';

    const toast = useToast();
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        itemid: Number,
        userid: Number,
    });

    const emit = defineEmits(['imported']);

    /**
     * Import grade for single user
     */
     function import_grade() {

        moodleFetch(
            'local_gugrades_import_grade',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
            }
        )
        .then((result: any) => {
            const success = result['success'];
            if (success) {
                toast.success(mstringstore.getMstring('gradeimporteduser'));
            } else {
                toast.warning(mstringstore.getMstring('nothingtoimport'));
            }
            emit('imported');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }
</script>