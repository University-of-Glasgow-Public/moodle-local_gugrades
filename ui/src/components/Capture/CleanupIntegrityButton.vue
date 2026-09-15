<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <MenuButton
        v-if="showbutton"
        @click="showconfirm = true"
        :disabled="processing"
        :warning="true"
        :wide="true"
        iconName="Bomb"
    >
        {{ buttonLabel }}
    </MenuButton>

    <ConfirmModal :show="showconfirm" :message="confirmMessage" @confirm="confirmcleanup"></ConfirmModal>
    <PleaseWait v-if="processing"></PleaseWait>
</template>

<script setup lang="ts">
    import { ref, onMounted, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import MenuButton from '../Common/MenuButton.vue';
    import { useToast } from 'vue-toastification';

    interface iError {
        gradeitemid: number;
        itemname: string;
        error: string;
        errortype?: string;
        userid?: number;
    }

    const props = defineProps<{
        errors: iError[];
    }>();

    const emits = defineEmits<{
        (e: 'cleaned', payload: { cleaned: number; erroritems: iError[] }): void;
    }>();

    const showconfirm = ref(false);
    const processing = ref(false);
    const debug = ref({});
    const caps = ref({
        removeuserdata: false,
        removeremovedassessment: false,
    });

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs(mstringstore);
    const toast = useToast();

    const buttonLabel = computed(() =>
        mstrings.value.integrity_cleanup_all || 'Clean all permitted'
    );

    const confirmMessage = computed(() =>
        mstrings.value.integrity_cleanup_all_confirm
        || 'This will remove MyGrades data for unenrolled users and deleted assessments you are permitted to fix. Other integrity issues are not changed. This cannot be undone.'
    );

    const showbutton = computed(() => {
        return props.errors.some((error) => canCleanError(error));
    });

    function canCleanError(error: iError): boolean {
        if (error.errortype === 'unenrolled_user') {
            return caps.value.removeuserdata && !!error.userid;
        }
        if (error.errortype === 'removed_gradeitem') {
            return caps.value.removeremovedassessment && !!error.gradeitemid;
        }
        return false;
    }

    function confirmcleanup(confirm: boolean) {
        if (!confirm) {
            showconfirm.value = false;
            return;
        }

        processing.value = true;
        showconfirm.value = false;

        moodleFetch('local_gugrades_cleanup_integrity_errors', {})
            .then((result: any) => {
                const cleaned = Number(result.cleaned || 0);
                if (cleaned > 0) {
                    toast.success(
                        mstrings.value.integrity_cleanup_all_success
                        || 'Permitted integrity issues have been cleaned.'
                    );
                } else {
                    toast.warning(
                        mstrings.value.integrity_cleanup_all_none
                        || 'No integrity issues could be cleaned with your current permissions.'
                    );
                }
                emits('cleaned', {
                    cleaned,
                    erroritems: result.erroritems || [],
                });
            })
            .catch((error) => {
                window.console.error(error);
                debug.value = error;
            })
            .finally(() => {
                processing.value = false;
            });
    }

    onMounted(async () => {
        try {
            const [removeuserdata, removeremovedassessment]: any[] = await Promise.all([
                moodleFetch('local_gugrades_has_capability', {
                    capability: 'local/gugrades:removeuserdata',
                }),
                moodleFetch('local_gugrades_has_capability', {
                    capability: 'local/gugrades:removeremovedassessment',
                }),
            ]);

            caps.value = {
                removeuserdata: !!removeuserdata.hascapability,
                removeremovedassessment: !!removeremovedassessment.hascapability,
            };
        } catch (error: any) {
            window.console.error(error);
            debug.value = error;
        }
    });
</script>
