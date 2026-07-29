<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UButton v-if="hascapability" variant="error" class="mr-1" @click="showconfirm = true">{{ mstrings.resetcourse }}</UButton>

    <ConfirmModal :show="showconfirm" :message="mstrings.resetcourseconfirm" @confirm="confirmdelete"></ConfirmModal>
</template>

<script setup lang="ts">
    import {ref, onMounted, inject} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useToast } from "vue-toastification";
    import UButton from '../Common/UButton.vue';

    const hascapability = ref(false);
    const showconfirm = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    /**
     * Process confirmation
     */
    function confirmdelete(confirmyes: boolean) {

        if (confirmyes) {
            moodleFetch(
                'local_gugrades_reset',
                {}
            )
            .then(() => {
                toast.success(mstringstore.getMstring('resetsuccess'))
            })
            .catch((error) => {
                window.console.error(error);
                debug.value = error;
            });
        }

        showconfirm.value = false;
    }

    /**
     * Check capability
     */
    onMounted(() => {

        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:resetcourse'
            }
        )
        .then((result: any) => {
            hascapability.value = result['hascapability'];
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });

    });

</script>