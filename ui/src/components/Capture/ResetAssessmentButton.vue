<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton v-if="hascapability" color="error" class="tw:mr-1" @click="showconfirm = true">
        {{ mstrings.resetassessment }}
    </TwButton>

    <ConfirmModal :show="showconfirm" :message="mstrings.resetassessmentconfirm" @confirm="confirmreset"></ConfirmModal>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useToast } from "vue-toastification";
    import TwButton from '../Tailwind/TwButton.vue';

    const props = defineProps<{
        itemid: number;
    }>();

    const emits = defineEmits(['reset']);

    const hascapability = ref(false);
    const showconfirm = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const toast = useToast();

    /**
     * Reset this assessment
     * @param boolean confirm
     */
    function confirmreset(confirm: boolean) {
        if (!confirm) {
            showconfirm.value = false;
            return;
        }

        moodleFetch(
            'local_gugrades_reset_grade_item',
            {
                gradeitemid: props.itemid,
            }
        )
        .then(() => {
            toast.success(mstrings.value.resetassessmentsuccess);
            emits('reset');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
        .finally(() => {
            showconfirm.value = false;
        });
    }

    /**
     * Check capability
     */
    onMounted(() => {
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:resetcourse',
            }
        )
        .then((result: any) => {
            hascapability.value = !!result.hascapability;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    });
</script>
