<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a class="tw:ml-1 tw:tooltip tw:cursor-pointer" aria-label="Bulk edit" data-tip="Bulk edit" @click.prevent="cog_clicked">
        <Cog6ToothIcon class="tw:size-6 tw:text-yellow-500"></Cog6ToothIcon>
    </a>
    <a
        v-if="candelete"
        class="tw:ml-1 tw:tooltip tw:cursor-pointer"
        aria-label="Delete column"
        :data-tip="mstringstore.getMstring('deletecolumn')"
        @click.prevent="delete_clicked"
    >
        <TrashIcon class="tw:size-6 tw:text-red-600"></TrashIcon>
    </a>
    <ConfirmModal
        :show="showconfirm"
        :message="mstringstore.getMstring('deletecolumnconfirm')"
        @confirm="confirmdelete"
    />
    <span v-if="processingdelete" class="tw:ml-2 tw:text-sm tw:text-gray-600">{{ mstrings.pleasewait }}</span>
</template>

<script setup lang="ts">
    import { onMounted, ref, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import ConfirmModal from '@/components/Common/ConfirmModal.vue';
    import { useToast } from "vue-toastification";
    import { Cog6ToothIcon, TrashIcon } from '@heroicons/vue/24/outline';

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