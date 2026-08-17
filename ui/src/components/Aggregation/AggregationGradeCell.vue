<template>
    <div class="inline-flex gap-2 align-middle">

        <!-- add/override grade -->
        <OverrideGrade
            v-if="gradeobject.available"
            :itemid = "column.gradeitemid"
            :categoryid = "column.categoryid"
            :selectedcategoryid = "level1category"
            :userid = "user.id"
            :gradehidden = "gradeobject.hidden"
            :overridden = "gradeobject.overridden"
            :itemname = "column.fullname"
            :name = "user.displayname"
            :showweights = "column.showweights"
            :released = "column.released"
            :caneditgrades = "caneditgrades"
            :position="beforehalfway ? 'below' : 'above'"
            @gradeadded = "grade_changed(user.id)"
        ></OverrideGrade>

        <!-- strikethrough if data is dropped -->
        <!-- bold if admin -->
        <UTooltip :class="itemclasses(gradeobject)" class="p-0.5" :text="itemtooltip(gradeobject)" :position="beforehalfway ? 'below' : 'above'">
            <s v-if="gradeobject.dropped">
                <b v-if="user.isadmin">{{ displaygrade }}</b>
                <GradeColor v-else :grade="displaygrade" :strikethrough="true"></GradeColor>
            </s>
            <span v-else>
                <b v-if="gradeobject.isadmin">{{ displaygrade }}</b>
                <GradeColor v-else :grade="displaygrade"></GradeColor>
            </span>
        </UTooltip>
    </div>
</template>

<script setup lang="ts">
    import { computed, onMounted } from 'vue';
    import UTooltip from '../Common/UTooltip.vue';
    import GradeColor from '../Common/GradeColor.vue';
    import OverrideGrade from './OverrideGrade.vue';
    import type { IColumn } from '@/js/Interfaces';
    import type { IUserField } from '@/js/Interfaces';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    interface IProps {
        user: Record<string, any>;
        column: IColumn;
        level1category: number;
        caneditgrades: boolean;
        beforehalfway: boolean;
    }

    const props = defineProps< IProps >();

    /**
     * Each grade object is indexed in the user prop by a key 
     * called fieldname in the column prop. Got it?
     * 
     */
    const gradeobject = computed(() => {
        return props.user[props.column.fieldname];
    });

    const displaygrade = computed(() => {
        const index = props.column.fieldname;
        return props.user[index].data;
    });

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['gradeadded']);

    /**
     * Get tooltip text for bordered items
     */
    function itemtooltip(item: IUserField) {
        if (item.overridden) {
            return mstringstore.getMstring('tooltipoverridden');
        }
        if (item.hidden) {
            return mstringstore.getMstring('tooltiphidden');
        }
        return '';
    }

    /**
     * Work out border classes for item
     */
    function itemclasses(item: IUserField) {
        if (item.overridden) {
            return ['border-2', 'border-solid', 'border-red-600', 'rounded-lg'];
        }
        if (item.hidden) {
            return ['border-2', 'border-solid', 'border-brand-light-yellow', 'rounded-lg'];
        }
        return [];
    }

    /**
     * Grade changed in override function
     */
    function grade_changed(userid: number) {
        emits('gradeadded', userid);
    }

    onMounted(() => {
    })
</script>