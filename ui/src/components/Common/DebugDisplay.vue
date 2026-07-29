<template>
    <VueModal v-model="showdebugmodal" :enableClose="false" modalClass="rounded max-w-3xl" title="A serious error has occurred">
        <UAlert class="my-4" variant="error">A serious error has occurred and MyGrades cannot continue.</UAlert>
        <ul>
            <li>
                CourseID: <pre>{{ moodlecourseid }}</pre>
            </li>
            <li v-for="(item, index) in errors">
                {{ index }}: <pre>{{ item }}</pre>
            </li>
        </ul>
        <UAlert class="my-4">
            <b>Please copy all of this data and send to IT Services / Help Desk for attention</b><br />
            You can then continue.
        </UAlert>
        <div class="mt-2 text-center">
            <UButton variant="primary" @click="reload_clicked">Close and continue</UButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {computed} from 'vue';
    import UAlert from './UAlert.vue';
    import UButton from './UButton.vue';

    const props = defineProps({
        debug: {
            type: Object,
            default: () => ({})
        }
    });

    const moodlecourseid = computed(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const courseid = urlParams.get('courseid');

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

    function reload_clicked() {
        window.location.reload()
    }
</script>

<style>
    pre {
        white-space: pre-wrap !important;
    }
</style>