<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="flex justify-center" style="min-width: 250px;">
        <FormKit
            type="select"
            name="admingrades_select"
            outer-class="w-42 pr-1"
            v-model="admingrade"
            :options="adminmenu"
            @input="input_updated"
        ></FormKit>
        <FormKit
            v-if="!props.usescale"
            outer-class="w-42 pl-0"
            type="text"
            number="float"
            :validation="gradevalidation"
            validation-visibility="live"
            maxlength="8"
            name="grade_select"
            v-model="grade"
            :disabled="admingrade != 'GRADE'"
            @input="input_updated"
        ></FormKit>
        <FormKit
            v-if="props.usescale"
            type="select"
            :placeholder="mstrings.scale"
            outer-class="w-42 pl-0"
            :disabled="admingrade != 'GRADE'"
            name="scale_select"
            v-model="grade"
            :options="scalemenu"
            @input="input_updated"
        ></FormKit>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { IMenuItem, ICaptureUser } from '@/js/Interfaces';

    // (item.id is current userid)
    // (item.reason is the reason/gradetype)
    // (item.other is the other text)
    // (item.gradeitemid)
    interface ICaptureCellProps {
        item: ICaptureUser;
        gradeitemid: number;
        categoryid: number;
        column: string;
        columnid: number;
        other: string;
        notes: string;
        gradetype: string;
        usescale: boolean;
        scalemenu: IMenuItem[];
        adminmenu: IMenuItem[];
        grademax: number;
        cancelled: boolean;
        /** Only true when the bulk-edit Save button was clicked. */
        shouldsave: boolean;
    }

    const props = defineProps< ICaptureCellProps >();

    const grade = ref('');
    const debug = ref({});
    const admingrade = ref('GRADE');
    const edited = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['gradewritten', 'gradecancel', 'validitychange']);

    // validation depends on grademax
    // optional: empty cells are allowed in bulk edit (unchanged / skip on save)
    const gradevalidation = computed<[string, ...any[]][]>(() => {
        return [
            ['optional'],
            ['number'],
            ['between', 0, props.grademax],
        ];
    });

    let   originalgrade = '';
    let   originaladmingrade = '';

    /**
     * Has the cell value changed from what was loaded?
     */
    function has_changed(): boolean {
        return (originalgrade != grade.value) || (originaladmingrade != admingrade.value);
    }

    /**
     * Is the current points (or scale) value safe to save?
     * Empty points cells are OK (unchanged / skip); edited invalid numbers are not.
     */
    function is_value_valid(): boolean {
        if (admingrade.value != 'GRADE') {
            return true;
        }
        if (props.usescale) {
            return String(grade.value ?? '').trim() !== '';
        }
        const raw = String(grade.value ?? '').trim();
        if (raw === '') {
            return true;
        }
        const n = Number(raw);
        if (!Number.isFinite(n)) {
            return false;
        }
        return n >= 0 && n <= props.grademax;
    }

    /**
     * Should this cell block the bulk Save button?
     */
    function is_blocking_invalid(): boolean {
        return has_changed() && !is_value_valid();
    }

    /**
     * Tell parent whether this cell currently blocks Save.
     */
    function emit_validity() {
        emits('validitychange', {
            userid: props.item.id,
            blocking: is_blocking_invalid(),
        });
    }

    /**
     * Strip an optional raw points suffix from a displayed scale band.
     * e.g. "B1 (17.11111)" -> "B1"
     */
    function scaleDisplayBand(displayvalue: string): string {
        return displayvalue.replace(/\s*\([^)]*\)\s*$/, '').trim();
    }

    /**
     * Does a scale menu label match the value shown in the table cell?
     */
    function scaleLabelMatches(label: string, displayvalue: string): boolean {
        if (label == displayvalue) {
            return true;
        }

        const band = scaleDisplayBand(displayvalue);
        const labelband = label.split(':')[0];

        return band === labelband || label.startsWith(band + ':');
    }

    /**
     * Find the scale menu value for a displayed grade.
     */
    function findScaleValue(displayvalue: string, scalemenu: IMenuItem[], rawgrade?: number | null): string {
        if (rawgrade !== undefined && rawgrade !== null) {
            const rawvalue = String(rawgrade);
            const byRaw = scalemenu.find((item) => String(item.value) === rawvalue);
            if (byRaw) {
                return rawvalue;
            }
        }

        for (const scaleitem of scalemenu) {
            if (scaleLabelMatches(scaleitem.label, displayvalue)) {
                return String(scaleitem.value);
            }
        }

        return '';
    }

    /**
     * Does the displayed value match an admin grade menu entry?
     */
    function adminGradeMatches(adminitem: IMenuItem, displayvalue: string): boolean {
        return adminitem.value == displayvalue
            || adminitem.label == displayvalue
            || adminitem.label.startsWith(displayvalue + ' -');
    }

    /**
     * Watch out for cancel being clicked.
     * This carry on because the prop doesn't get updated when
     * unMount in progress.
     * Each cell will emit so CaptureTable debounces it to avoid multiple
     * page refreshes.
     */
    watch(
        () => props.cancelled,
        () => {
            emits('gradecancel');
        }
    );

    /**
     * Re-evaluate whether Save should be blocked when inputs change.
     */
    watch(
        [grade, admingrade],
        () => {
            emit_validity();
        }
    );

    /**
     * Mostly, set up initial values for the form.
     */
    onMounted(() => {

        const value = props.item[props.column];
        const graderecord = props.item.grades?.find((grade) => grade.columnid === props.columnid);
        const rawgrade = graderecord?.rawgrade;

        let adminMatched = false;
        props.adminmenu.forEach((adminitem) => {
            if (adminGradeMatches(adminitem, value)) {
                admingrade.value = adminitem.value;
                adminMatched = true;
            }
        });

        if (adminMatched) {
            grade.value = '';
        } else if (props.usescale) {
            grade.value = findScaleValue(value, props.scalemenu, rawgrade);
        } else {
            grade.value = value;
        }

        originalgrade = grade.value;
        originaladmingrade = admingrade.value;
        emit_validity();
    });

    /**
     * Change made to edit box
     *
     */
    function input_updated() {

        // If anything has changed, flag that we will need
        // to save it at some point.
        edited.value = true;
        emit_validity();
    }

    /**
     * Request recalculate single user.
     * Note: no need to wait for response
     */
    function recalculate_user(userid: number) {

        moodleFetch(
            'local_gugrades_recalculate',
            {
                gradecategoryid: props.categoryid,
                userid: userid,
            }
        )
        .catch(error => {
            console.error(error);
        });
    }

    /**
     * When this component closes, save only if bulk-edit Save was clicked.
     * Navigating away / remounting must not persist pending edits.
     */
    onBeforeUnmount(() => {

        // Clear any Save block for this cell.
        emits('validitychange', { userid: props.item.id, blocking: false });

        // Discard unless the user explicitly clicked Save.
        if (!props.shouldsave || props.cancelled || !has_changed()) {
            return;
        }

        // Never persist invalid numbers — out-of-range grades break MyGrades capture.
        if (!is_value_valid()) {
            return;
        }

        // Empty points cell with GRADE selected: nothing to write.
        if (admingrade.value == 'GRADE' && !props.usescale && String(grade.value ?? '').trim() === '') {
            return;
        }

        const userid = props.item.id;
        const reason = props.gradetype;
        const other = props.other;
        const gradeitemid = props.gradeitemid;
        const saveadmingrade = admingrade.value == 'GRADE' ? '' : admingrade.value;
        const savescale = (admingrade.value == 'GRADE') && props.usescale ? grade.value : '0';
        const savegrade = (admingrade.value == 'GRADE') && !props.usescale ? grade.value : '0';
        const notes = props.notes;
        const parsedgrade = parseFloat(savegrade);

        if ((admingrade.value == 'GRADE') && !props.usescale && !Number.isFinite(parsedgrade)) {
            return;
        }

        moodleFetch(
            'local_gugrades_write_additional_grade',
            {
                gradeitemid: gradeitemid,
                userid: userid,
                admingrade: saveadmingrade,
                reason: reason,
                other: other,
                scale: savescale,
                grade: parsedgrade,
                notes: notes,
            }
        )
        .then(() => {
            recalculate_user(userid);
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });

        emits('gradewritten');
    });

</script>