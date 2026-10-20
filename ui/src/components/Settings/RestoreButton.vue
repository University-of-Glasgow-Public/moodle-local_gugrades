<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UButton v-if="hascapability" variant="secondary" class="mr-1" :loading="restoring" :disabled="restoring" @click="showconfirm = true">{{ mstrings.restoregrades }}</UButton>

    <ConfirmModal :show="showconfirm" :message="mstrings.restoregradesconfirm" @confirm="confirmrestore"></ConfirmModal>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useToast } from "vue-toastification";
    import UButton from '../Common/UButton.vue';

    const hascapability = ref(false);
    const showconfirm = ref(false);
    const restoring = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    /**
     * Process confirmation
     */
    function confirmrestore(confirmyes: boolean) {
        showconfirm.value = false;
        if (!confirmyes) {
            return;
        }

        restoring.value = true;
        moodleFetch(
            'local_gugrades_restore_grades',
            {}
        )
        .then((result: any) => {
            toast.success(result.message);
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
        .finally(() => {
            restoring.value = false;
        });
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
