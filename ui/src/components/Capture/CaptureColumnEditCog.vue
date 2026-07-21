<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UTooltip position="below" class="ml-1" aria-label="Bulk edit" text="Bulk edit" @click.prevent="cog_clicked">
        <Settings class="text-brand-light-yellow cursor-pointer"></Settings>
    </UTooltip>
    <UTooltip
        v-if="candelete"
        position="below"
        class="ml-1"
        aria-label="Delete column"
        :text="mstringstore.getMstring('deletecolumn')"
        @click.prevent="delete_clicked"
    >
        <Trash2 class="text-brand-dark-pink cursor-pointer"></Trash2>
    </UTooltip>
    <ConfirmModal
        :show="showconfirm"
        :message="mstringstore.getMstring('deletecolumnconfirm')"
        @confirm="confirmdelete"
    />
    <PleaseWait v-if="processingdelete"></PleaseWait>
</template>

<script setup lang="ts">
    import { onMounted, ref, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import { Settings, Trash2 } from '@lucide/vue';
    import UTooltip from '../Common/UTooltip.vue';

    const props = defineProps({
        itemid: {
            type: Number,
            default: 0
        },
        header: {
            type: Object,
            default: {}
        },
        active: Boolean,
    });

    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['editcolumn', 'columnchanged']);
    const showconfirm = ref(false);
    const hasadmincap = ref(false);
    const processingdelete = ref(false);

    const candelete = computed(() => {
        if (!hasadmincap.value) {
            return false;
        }
        // Only real DB-backed columns can be deleted.
        if (!props.header?.id || props.header.id === 0) {
            return false;
        }
        // Never allow deleting FIRST/PROVISIONAL columns.
        if (props.header.gradetype === 'FIRST' || props.header.gradetype === 'PROVISIONAL') {
            return false;
        }
        return true;
    });

    onMounted(() => {
        moodleFetch('local_gugrades_has_capability', {
            capability: 'local/gugrades:resetcourse',
        })
        .then((result: any) => {
            hasadmincap.value = !!result.hascapability;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    });

    /**
     * Cog wheel has been clicked
     */
    function cog_clicked() {

        emits('editcolumn');
    }

    function delete_clicked() {
        showconfirm.value = true;
    }

    function confirmdelete(confirm: boolean) {
        showconfirm.value = false;

        if (!confirm) {
            return;
        }

        processingdelete.value = true;
        moodleFetch(
            'local_gugrades_delete_capture_column',
            {
                gradeitemid: props.itemid,
                columnid: props.header.id,
            }
        )
        .then(() => {
            emits('columnchanged');
            processingdelete.value = false;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        })
        .finally(() => {
            processingdelete.value = false;
        });
    }
</script>