<template>
    <div class="mt-2">
        <small>
            <DismissableAlert v-if="!props.gradesupported" alertstyle="danger" :message="mstrings.gradenotsupported"></DismissableAlert>

            <DismissableAlert v-if="!props.aggregationsupported" alertstyle="danger" :message="mstrings.aggregationnotsupported + ' (' + unsupportedscales + ')'"></DismissableAlert>

            <DismissableAlert v-if="props.gradehidden" alertstyle="warning" :message="mstrings.gradehidden"></DismissableAlert>

            <DismissableAlert v-if="props.gradelocked" alertstyle="warning" :message="mstrings.gradelocked"></DismissableAlert>

            <DismissableAlert v-if="props.noids" alertstyle="warning" :message="mstrings.noids"></DismissableAlert>
        </small>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import DismissableAlert from '@/components/Common/DismissableAlert.vue';

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
</script>