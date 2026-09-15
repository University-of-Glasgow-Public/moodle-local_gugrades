<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <MenuButton v-if="hascapability" @click="showconfirm = true" :disabled="processing" :warning="true" :wide="removed" iconName="Bomb">
        {{ buttonLabel }}
    </MenuButton>

    <ConfirmModal :show="showconfirm" :message="confirmMessage" @confirm="confirmreset"></ConfirmModal>
    <PleaseWait v-if="processing"></PleaseWait>
</template>

<script setup lang="ts">
    import {ref, onMounted, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import MenuButton from '../Common/MenuButton.vue';
    import { useToast } from "vue-toastification";

    const props = withDefaults(defineProps<{
        itemid: number;
        small?: boolean;
        /** True when cleaning MyGrades data for a grade item deleted from the course. */
        removed?: boolean;
    }>(), {
        removed: false,
    });

    const emits = defineEmits(['reset']);

    const hascapability = ref(false);
    const showconfirm = ref(false);
    const processing = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const toast = useToast();

    const capability = computed(() =>
        props.removed
            ? 'local/gugrades:removeremovedassessment'
            : 'local/gugrades:resetassessment'
    );

    const buttonLabel = computed(() =>
        props.removed
            ? (mstrings.value.removeremovedassessment || 'Remove assessment data')
            : mstrings.value.resetassessment
    );

    const confirmMessage = computed(() =>
        props.removed
            ? (mstrings.value.removeremovedassessmentconfirm
                || 'This will delete all MyGrades data for this removed assessment and cannot be undone.')
            : mstrings.value.resetassessmentconfirm
    );

    const successMessage = computed(() =>
        props.removed
            ? (mstrings.value.removeremovedassessmentsuccess
                || 'MyGrades data for the removed assessment has been deleted.')
            : mstrings.value.resetassessmentsuccess
    );

    /**
     * Reset this assessment
     * @param boolean confirm
     */
    function confirmreset(confirm: boolean) {
        if (!confirm) {
            showconfirm.value = false;
            return;
        }

        processing.value = true;

        moodleFetch(
            'local_gugrades_reset_grade_item',
            {
                gradeitemid: props.itemid,
            }
        )
        .then(() => {
            toast.success(successMessage.value);
            emits('reset');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Check capability
     */
    onMounted(() => {
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: capability.value,
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
