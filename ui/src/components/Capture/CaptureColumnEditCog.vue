<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UTooltip class="ml-1" aria-label="Bulk edit" text="Bulk edit" @click.prevent="cog_clicked">
        <Settings class="text-brand-light-yellow"></Settings>
    </UTooltip>
    <UTooltip
        v-if="candelete"
        class="ml-1"
        aria-label="Delete column"
        :text="mstringstore.getMstring('deletecolumn')"
        @click.prevent="delete_clicked"
    >
        <Trash2 class="text-brand-dark-pink"></Trash2>
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
    import { useToast } from "vue-toastification";
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

    const toast = useToast();
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
        if (!props.header?.columnid || props.header.columnid === 0) {
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

        moodleFetch(
            'local_gugrades_get_capture_cell_form',
            {
                gradeitemid: props.itemid,
            }
        )
        .then((result: any) => {
            const usescale = result.usescale;
            const grademax = result.grademax;
            const scalemenu = result.scalemenu;
            const adminmenu = result.adminmenu;

            // Add 'use grade' option onto front of adminmenu
            adminmenu.unshift({
                value: 'GRADE',
                label: mstringstore.getMstring('selectnormalgradeshort'),
            });

            // send all this stuff back
            emits('editcolumn', {
                columnname: props.header.value,
                gradetype: props.header.gradetype,
                other: props.header.other,
                columnid: props.header.columnid,
                usescale: usescale,
                grademax: grademax,
                scalemenu: scalemenu,
                adminmenu: adminmenu,
                notes: '',
            });
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }
        function delete_clicked() {
        showconfirm.value = true;
    }
    function confirmdelete(confirm: boolean) {
        if (!confirm) {
            showconfirm.value = false;
            return;
        }

        processingdelete.value = true;
        moodleFetch(
            'local_gugrades_delete_capture_column',
            {
                gradeitemid: props.itemid,
                columnid: props.header.columnid,
            }
        )
        .then(() => {
            toast.success(mstringstore.getMstring('deletecolumnsuccess'));
            emits('columnchanged');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }
</script>