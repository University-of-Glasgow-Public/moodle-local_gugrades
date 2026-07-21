<template>
    <GradeColor v-if="!isbulkedit" :grade="displaygrade" />
    <BulkEditCell
        v-if="isbulkedit"
        :usescale="form!.usescale"
        :grademax="form!.grademax"
        :adminmenu="form!.adminmenu"
        :scalemenu="form!.scalemenu"
        @update="bulk_edit_update"
    />
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import GradeColor from '@/components/Common/GradeColor.vue';
    import type { ICaptureCellForm } from '@/js/Interfaces';
    import BulkEditCell from './BulkEditCell.vue';

    interface iProps {
        user: Record<string, any>;   // Keys are strings, values are anything
        column: Record<string, any>; // Keys are strings, values are anything
        form: ICaptureCellForm | null;  
        editcolumnid: number;
    }

    const props = defineProps< iProps >();

    const emits = defineEmits(['update'])

    const displaygrade = computed(() => {
        const index = 'GRADE' + props.column.id;
        return props.user[index];
    });

    const isbulkedit = computed(() => {
        return props.editcolumnid == props.column.id;
    });

    /**
     * Handle bulk edit updating grade
     */
    function bulk_edit_update(grade: any) {
        emits('update', grade);
    }

</script>