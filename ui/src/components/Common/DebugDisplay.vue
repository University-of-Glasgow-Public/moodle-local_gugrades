<template>
    <VueModal v-model="showdebugmodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" title="A serious error has occurred">
        <TwAlert class="tw:my-4" color="error">A serious error has occurred and MyGrades cannot continue.</TwAlert>
        <ul>
            <li>
                CourseID: <pre>{{ moodlecourseid }}</pre>
            </li>
            <li v-for="(item, index) in errors">
                {{ index }}: <pre>{{ item }}</pre>
            </li>
        </ul>
        <TwAlert class="tw:my-4">
            <b>Please copy all of this data and send to IT Services / Help Desk for attention</b><br />
            You can then continue.
        </TwAlert>
        <div class="tw:mt-2 tw:text-center">
            <a class="tw:btn tw:btn-primary" href="javascript:window.location.reload(true)">Close and continue</a>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {computed} from '@vue/runtime-core';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';

    const props = defineProps({
        debug: {
            type: Object,
            default: () => ({})
        }
    });

    const moodlecourseid = computed(() => {
        const GU = window.GU;
        const courseid = GU.courseid;

        return courseid;
    });

    /**
     * Debug should be an array but it's sometimes a string
     */
    const errors = computed(() => {
        if (typeof props.debug === "string") {
            return {
                message: props.debug,
            }
        } else {
            return props.debug;
        }
    });

    const showdebugmodal = computed(() => {
        return Object.keys(props.debug).length !== 0
    });
</script>

<style>
    pre {
        white-space: pre-wrap !important;
    }
</style>