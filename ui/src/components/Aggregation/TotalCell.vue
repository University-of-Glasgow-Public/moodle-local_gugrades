<template>
    <div class="inline-flex gap-2">

        <!-- add/override for total grade -->
        <OverrideGrade
            :toplevel="toplevel"
            :itemid = "gradeitemid"
            :selectedcategoryid = "level1category"
            :categoryid = "categoryid"
            :userid = "user.id"
            :gradehidden = "false"
            :overridden = "user.overridden"
            :itemname = "user.itemname"
            :name = "user.displayname"
            :showweights = "showweights"
            :released = "false"
            :caneditgrades = "caneditgrades"
            :position="beforehalfway ? 'below' : 'above'"
            @gradeadded = "grade_changed(user.id)"
        ></OverrideGrade>

        <span v-if="user.error">{{ user.error }}</span>
        <UTooltip v-else :class="itemclasses(props.user)" class="p-0.5" :text="itemtooltip(user)" :position="beforehalfway ? 'below' : 'above'">
            <GradeColor  :grade="user.displaygrade"></GradeColor>
        </UTooltip>
        <span v-if="user.alteredweight">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-light-yellow text-university-blue">
                ALTERED
            </span>
            <UBadge variant="warning" class="mt-1">ALTERED</UBadge>
        </span>
    </div>
</template>

<script setup lang="ts">
    import GradeColor from '../Common/GradeColor.vue';
    import OverrideGrade from './OverrideGrade.vue';
    import UBadge from '../Common/UBadge.vue';
    import UTooltip from '../Common/UTooltip.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    interface IProps {
        user: Record<string, any>;
        toplevel: boolean;
        gradeitemid: number;
        level1category: number;
        categoryid: number;
        showweights: boolean;
        caneditgrades: boolean;
        beforehalfway: boolean;
    }

    const props = defineProps< IProps >();

    const emits = defineEmits(['gradeadded']);

    const mstringstore = useMstrings();

    /**
     * Grade changed in override function
     */
    function grade_changed(userid: number) {
        emits('gradeadded', userid);
        console.log('CHANGED', userid);
    }

    /**
     * Get tooltip text for bordered items
     */
    function itemtooltip(item: Record<string, any>) {
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
    function itemclasses(item: Record<string, any>) {
        if (item.overridden) {
            return ['border-2', 'border-solid', 'border-red-600', 'rounded-lg'];
        }
        if (item.hidden) {
            return ['border-2', 'border-solid', 'border-brand-light-yellow', 'rounded-lg'];
        }
        return [];
    }
</script>