<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a @click.prevent="import_grade()" class="cursor-pointer">{{ mstrings.importusergrade }}</a>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';

    const toast = useToast();
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        itemid: Number,
        userid: Number,
        close: Function,
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

        if (props.close) {
            props.close();
        }
    }
</script>