<template>
    <div class="flex w-full items-center justify-start text-left" :class="dualgrades ? 'gap-0.5' : 'gap-2'">

        <!-- add/override for total grade -->
        <OverrideGrade
            v-if="!firstsitting"
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

        <span v-if="firstsitting ? user.error1st : user.error">{{ firstsitting ? user.error1st : user.error }}</span>
        <UTooltip v-else :class="firstsitting ? [] : itemclasses(props.user)" class="min-w-11 p-0.5" :text="firstsitting ? '' : itemtooltip(user)" :position="beforehalfway ? 'below' : 'above'">
            <b v-if="firstsitting && user.isadmin1st" class="inline-flex h-6 min-w-10 items-center justify-center px-1">{{ user.displaygrade1st }}</b>
            <GradeColor v-else :grade="firstsitting ? user.displaygrade1st : user.displaygrade" :otherclasses="['min-w-10']"></GradeColor>
        </UTooltip>
        <span v-if="!firstsitting && user.alteredweight">
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
        firstsitting?: boolean;
        dualgrades?: boolean;
    }

    const props = defineProps< IProps >();

    const emits = defineEmits(['gradeadded']);

    const mstringstore = useMstrings();

    /**
     * Grade changed in override function
     */
    function grade_changed(userid: number) {
        emits('gradeadded', userid);
    }

    /**
     * Get tooltip text for bordered items
     */
    function itemtooltip(item: Record<string, any>) {
        if (item.overridden) {
            return mstringstore.getMstring('tooltipoverridden');
        }
        if (item.capped) {
            return mstringstore.getMstring('resitcapped');
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
        // MGU-1596: Award capped because of a resit.
        if (item.capped) {
            return ['border-2', 'border-solid', 'border-orange-500', 'rounded-lg'];
        }
        if (item.hidden) {
            return ['border-2', 'border-solid', 'border-brand-light-yellow', 'rounded-lg'];
        }
        return [];
    }
</script>