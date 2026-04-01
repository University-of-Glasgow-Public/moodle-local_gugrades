<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="add_multiple_button_click()" :disabled="!enable">
        {{ mstrings['addmultiple'] }}
    </TwButton>

    <VueModal v-model="showaddmultiplemodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['addmultiple']">
        <FormKit type="form" @submit="submit_form">
            <FormKit
                type="select"
                :label="mstrings['reasonforadditionalgrades']"
                name="reason"
                v-model="reason"
                :options="gradetypes"
                :placeholder="mstrings['selectareason']"
                validation="required"
            />
            <FormKit
                v-if = 'reason == "OTHER"'
                :label="mstrings['pleasespecify']"
                type="text"
                :placeholder="mstrings['pleasespecify']"
                name="other"
                v-model="other"
                validation="required"
                :validation-messages="{
                    required: 'This field is required.',
                }"
            />
            <FormKit
                type="textarea"
                label="Notes"
                :placeholder="mstrings['reasonforammendment']"
                name="notes"
                v-model="notes"
            />
        </FormKit>

        <div class="tw:flex tw:justify-end">
            <TwButton color="warning" @click="showaddmultiplemodal = false">{{ mstrings['cancel'] }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    interface GradeType {
        label: string;
        value: string;
    }

    const showaddmultiplemodal = ref(false);
    const gradetypes = ref<GradeType[]>([]);
    const reason = ref('');
    const notes = ref('');
    const other = ref('');
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits([
        'editcolumn'
    ]);

    const toast = useToast();

    const props = defineProps({
        enable: {
            type: Boolean,
            default: true,
        },
        itemid: Number,
    });

    /**
     * Button clicked
     */
    function add_multiple_button_click() {

        // Clear for new form
        other.value = '';
        reason.value = '';

        moodleFetch(
            'local_gugrades_get_gradetypes',
            {
                gradeitemid: props.itemid,
            }
        )
        .then((result: any) => {
            gradetypes.value = result.gradetypes;
        })
        .catch((error) => {
            window.console.error(error);
            showaddmultiplemodal.value = false;
            debug.value = error;
        });

        showaddmultiplemodal.value = true;
    }

    /**
     * Get all the details for the cell forms
     * This is called immediately after the submit_form() promise
     * completes.
     */
     function get_capture_cell_form(columnid: number) {

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
                label: mstrings.value['selectnormalgradeshort'],
            });

            // send all this stuff back
            emits('editcolumn', {
                columnname: 'GRADE' + columnid,
                gradetype: reason.value,
                other: other.value,
                usescale: usescale,
                grademax: grademax,
                scalemenu: scalemenu,
                adminmenu: adminmenu,
                notes: notes.value,
            });
        })
        .catch((error) => {
            window.console.error(error);
            showaddmultiplemodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Process form submission
     */
    function submit_form() {

        // Where reason looks like OTHER_nn,
        // It's an exiting other, the corresponding
        // label is 'other' and reason is 'OTHER'
        if (reason.value.startsWith('OTHER_')) {
            const gtype = gradetypes.value.find(o => o.value == reason.value);
            if (gtype) {
                reason.value = 'OTHER';
                other.value = gtype.label;
            }
        }

        moodleFetch(
            'local_gugrades_write_column',
            {
                gradeitemid: props.itemid,
                reason: reason.value,
                other: other.value,
                notes: notes.value,
            }
        )
        .then((result: any) => {
            const columnid = result.columnid;
            get_capture_cell_form(columnid);
        })
        .catch((error) => {
            window.console.error(error);
            showaddmultiplemodal.value = false;
            debug.value = error;
        });

        // close the modal
        showaddmultiplemodal.value = false;
    }
</script>