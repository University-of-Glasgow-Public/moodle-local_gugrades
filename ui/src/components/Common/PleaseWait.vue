<template>
    <VueModal v-model="showmodal" :enableClose="false" modalClass="tw:rounded tw:max-w-xl" :title="titletext">
        <div class="tw:flex tw:justify-center" >
            <div class="tw:border-solid tw:rounded-md tw:m-1 tw:p-2 tw:flex tw:justify-center" style="min-width: 300px">
                <span v-if="!showprogress" class="tw:loading tw:loading-ring tw:loading-xl"></span>
                <RadialProgress v-if="showprogress" :diameter="100" :totalSteps="100" :completedSteps="progress">{{ progress }}%</RadialProgress>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, onMounted, onUnmounted, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useIntervalFn } from '@vueuse/core';
    import { useMstrings } from '@/stores/mstrings.js';
    import RadialProgress from "vue3-radial-progress";
    import { moodleFetch } from '@/js/moodlefetch';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const showmodal = ref(false);
    const progress = ref(0);

    // Props are only defined for progress bar.
    // If you don't want a progress bar then props are not required
    const props = defineProps({
        uniqueid: {
            type: Number,
            default: 0
        },
        progresstype: {
            type: String,
            default: '',
        },
        staffuserid: {
            type: Number,
            default: 0,
        },
        message: {
            type: String,
            default: '',
        },
    });

    const titletext = computed(() => {
        return (props.message != '') ? props.message : mstrings.value['pleasewait'];
    });

    const showprogress = computed(() => {
        return (props.progresstype != '') && (progress.value >= 0);
    });

    const progressclass = computed(() => {
        if (progress.value < 33.3) {
            return 'bg-danger';
        }
        if (progress.value < 66.6) {
            return 'bg-info';
        }
        return 'bg-success';
    })

    const { pause, resume, isActive } = useIntervalFn(() => {
        if (props.progresstype != '') {
            const GU = window.GU;
            const courseid = GU.courseid;
            const fetchMany = GU.fetchMany;

            // Note the two additional parameters. They are
            // async = true
            // loginrequired = false
            //
            // Without loginrequired we'd hit moodle sessions which would stop this returning.
            // We also have to pass around the staff userid as that would not be available
            // outside a session.
            moodleFetch(
                'local_gugrades_get_progress',
                {
                    courseid: courseid,
                    uniqueid: props.uniqueid,
                    progresstype: props.progresstype,
                    staffuserid: props.staffuserid,
                },
                true,
                false)
            .then((result: any) => {
                progress.value = result.progress;
            })
            .catch((error) => {
                window.console.error(error);
            })
        }
    }, 1000)

    onMounted(() => {
        showmodal.value = true;
    });


    onUnmounted(() => {
        showmodal.value = false;
    });
</script>

<style>
    .vm {
        min-width: 300px !important;
    }
</style>