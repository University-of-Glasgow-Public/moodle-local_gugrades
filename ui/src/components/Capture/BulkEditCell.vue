<template>
    <div class="flex justify-start" style="min-width: 250px;">
        <FormKit
            type="select"
            name="admingrades"
            outer-class="w-42 pr-1"
            v-model="admingrade"
            :options="adminmenu"
        ></FormKit>
        <FormKit
            v-if="!usescale"
            outer-class="w-42 pl-0"
            type="text"
            :validation="gradevalidation"
            validation-visibility="live"
            maxlength="8"
            name="grade"
            v-model="gradeDisplay"
            :disabled="admingrade != 'GRADE'"
        ></FormKit>
        <FormKit
            v-if="usescale"
            type="select"
            :placeholder="mstrings.scale"
            outer-class="w-42 pl-0"
            :disabled="admingrade != 'GRADE'"
            name="scale"
            v-model="grade"
            :options="scalemenu"
        ></FormKit>
    </div>
</template>

<script setup lang="ts">
    import { ref, onMounted, computed, watch } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { IMenuItem, IMenuIntItem, ICaptureGrade } from '@/js/Interfaces';

    interface IBulkEditStore {
        admingrade: string;
        grade: number;
    }

    interface IBulkEdit {
        usescale: boolean;
        grademax: number;
        adminmenu: IMenuItem[];
        scalemenu: IMenuIntItem[];
        activegrade: ICaptureGrade;
        bulkselect: IBulkEditStore | null;
    }

    const props = defineProps< IBulkEdit >();

    const emits = defineEmits(['update']);

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const admingrade = ref('GRADE');
    const grade = ref(-1);

    /**
     * Watch both reactive refs simultaneously 
     */
    watch([admingrade, grade], ([newAdminGrade, newGrade]) => {
        emits('update', {
            admingrade: newAdminGrade,
            grade: newGrade,
        });
    });

    // Make sure the grade edit box (if points) only shows valid numbers.
    const gradeDisplay = computed({
        get: () => grade.value === -1 ? '' : grade.value,
        set: (value) => {
            grade.value = value === '' ? -1 : Number(value);
        }
    });

    /**
     * validation depends on grademax
     */
    const gradevalidation = computed<[string, ...any[]][]>(() => {
        return [
            ['optional'],
            ['number'],
            ['between', 0, props.grademax],
        ];
    });

    onMounted(() => {
        // If there's something in bulkselect then set that
        if (props.bulkselect) {
            admingrade.value = props.bulkselect.admingrade;
            grade.value = props.bulkselect.grade;

            return;
        }

        // Failing that, consider using existing grade. 
        if (!props.activegrade) {
            return;
        }

        if (props.activegrade.admingrade) {
            admingrade.value = props.activegrade.admingrade;
        }

        if (props.activegrade.rawgrade) {
            grade.value = props.activegrade.rawgrade;
        }
    });
</script>