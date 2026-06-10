<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="flex justify-center" style="min-width: 250px;">
        <FormKit
            type="select"
            name="admingrades"
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
            name="grade"
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
            name="scale"
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
    }

    const props = defineProps< ICaptureCellProps >();

    const grade = ref('');
    const debug = ref({});
    const admingrade = ref('GRADE');
    const edited = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['gradewritten', 'gradecancel']);

    // validation depends on grademax
    const gradevalidation = computed<[string, ...any[]][]>(() => {
        return [
            ['number'],
            ['between', 0, props.grademax],
        ];
    });

    let   originalgrade = '';
    let   originaladmingrade = '';

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
    });

    /**
     * Change made to edit box
     *
     */
    function input_updated() {

        // If anything has changed, flag that we will need
        // to save it at some point.
        edited.value = true;
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
            console.log(error);
        });
    }

    /**
     * When this component closes, save the data
     */
    onBeforeUnmount(() => {

        // if this cell hasn't been edited then nothing to do!
        if (props.cancelled || ((originalgrade == grade.value) && (originaladmingrade == admingrade.value))) {
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


        moodleFetch(
            'local_gugrades_write_additional_grade',
            {
                gradeitemid: gradeitemid,
                userid: userid,
                admingrade: saveadmingrade,
                reason: reason,
                other: other,
                scale: savescale,
                grade: parseFloat(savegrade),
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