<template>
    <AlertsBlock :errors="warnings" />
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
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