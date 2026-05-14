<template>
    <!--
    <div class="mt-2">
        <small>
            <DismissableAlert v-if="!props.gradesupported" alertstyle="danger" :message="mstrings.gradenotsupported"></DismissableAlert>

            <DismissableAlert v-if="!props.aggregationsupported" alertstyle="danger" :message="mstrings.aggregationnotsupported + ' (' + unsupportedscales + ')'"></DismissableAlert>

            <DismissableAlert v-if="props.gradehidden" alertstyle="warning" :message="mstrings.gradehidden"></DismissableAlert>

            <DismissableAlert v-if="props.gradelocked" alertstyle="warning" :message="mstrings.gradelocked"></DismissableAlert>

            <DismissableAlert v-if="props.noids" alertstyle="warning" :message="mstrings.noids"></DismissableAlert>
        </small>
    </div>
-->
    <AlertsBlock :errors="warnings" />
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import DismissableAlert from '@/components/Common/DismissableAlert.vue';
    import AlertsBlock from '../Common/AlertsBlock.vue';
    import type { IError } from '@/js/Interfaces'

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        gradesupported: Boolean,
        aggregationsupported: Boolean,
        unsupportedscales: String,
        gradehidden: Boolean,
        gradelocked: Boolean,
        noids: Boolean,
    });

    /**
     * Create array of warnings
     */
    const warnings = computed< IError[] >(() => {
        const w: IError[] = [];
        if (!props.aggregationsupported) {
            w.push({
                warning: mstrings.value.aggregationnotsupported!,
                help: mstrings.value.gradenotsupported_help!,
                level: 'error',
            });
        }
        if (!props.gradesupported) {
            w.push({
                warning: mstrings.value.gradenotsupported!,
                help: mstrings.value.aggregationnotsupported_help + ' ' + props.unsupportedscales,
                level: 'error',
            });
        }
        if (props.gradehidden) {
            w.push({
                warning: mstrings.value.gradehidden!,
                help: mstrings.value.gradehidden_help!,
                level: 'warning',
            });
        }
        if (props.gradelocked) {
            w.push({
                warning: mstrings.value.gradelocked!,
                help: mstrings.value.gradelocked_help!,
                level: 'warning',
            });
        }
        if (props.noids) {
            w.push({
                warning: mstrings.value.noids!,
                help: mstrings.value.noids_help!,
                level: 'warning',
            });
        }

        return w;
    })
</script>