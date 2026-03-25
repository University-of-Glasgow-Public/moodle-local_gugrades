<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="release_button_clicked" :disabled="!enable">
        <span v-if="props.released">
            <span v-if="grouprelease">{{ mstrings['unreleasegradesgroup'] }}</span>
            <span v-else>{{ mstrings['unreleasegrades'] }}</span>
        </span>
        <span v-if="!props.released">
            <span v-if="grouprelease">{{ mstrings['releasegradesgroup'] }}</span>
            <span v-else>{{ mstrings['releasegrades'] }}</span>
        </span>
    </TwButton>

    <VueModal v-model="showreleasemodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['releasegrades']">

        <div v-if="loading">
            <PleaseWait></PleaseWait>
        </div>

        <!-- Show if NOT already released -->
        <div v-if="!props.released">
            <TwAlert v-if="!props.released">
                {{ mstrings['releaseconfirm'] }}
                <p v-if="grouprelease" class="tw:mt-1"><b>{{ mstrings['releaseconfirmgroup'] }}</b></p>
            </TwAlert>

            <TwAlert v-if="props.released">
                {{ mstrings['releaseconfirmstern'] }}
                <p v-if="grouprelease" class="mt-1"><b>{{ mstrings['releaseconfirmgroup'] }}</b></p>
            </TwAlert>

            <div class="tw:mt-5 flex justify-start">
                <TwButton color="primary" @click="release_grades">{{ mstrings['yesrelease'] }}</TwButton>
                <TwButton color="warning" @click="showreleasemodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>
        </div>

        <!-- Show if already released -->
        <div v-if="props.released">
            <h4>Revert release of grades</h4>
            <TwAlert>
                {{ mstrings['removerelease'] }}
                <p v-if="grouprelease" class="mt-1"><b>{{ mstrings['removereleasegroup'] }}</b></p>
            </TwAlert>

            <div class="tw:mt-5 flex justify-start">
                <TwButton color="primary" @click="revert_release">{{ mstrings['yesunrelease'] }}</TwButton>
                <TwButton color="warning" @click="showreleasemodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, inject, computed} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import PleaseWait from '@/components/PleaseWait.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import { useLogo } from '@/js/monochromelogo.js';
    import { useMstrings } from '@/stores/mstrings.js';

    const showreleasemodal = ref(false);
    const loading = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits(['released']);

    const toast = useToast();

    const {monochrome, updateLogo} = useLogo();

    const props = defineProps({
        enable: {
            type: Boolean,
            default: true,
        },
        gradeitemid: Number,
        groupid: Number,
        released: Boolean,
    });

    const grouprelease = computed(() => {
        return props.groupid > 0;
    });

    /**
     * Release button clicked
     */
    function release_button_clicked() {
        loading.value = false;
        showreleasemodal.value = true;
    }

    /**
     * Release grades on button click
     */
    function release_grades() {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        loading.value = true;

        fetchMany([{
            methodname: 'local_gugrades_release_grades',
            args: {
                courseid: courseid,
                gradeitemid: props.gradeitemid,
                groupid: props.groupid,
                revert: false,
            }
        }])[0]
        .then(() => {
            emit('released');
            showreleasemodal.value = false;
            updateLogo();
            toast.success(mstrings.value['gradesreleased']);
        })
        .catch((error) => {
            window.console.error(error);
            showreleasemodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Revert release grades on button click
     */
     function revert_release() {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        loading.value = true;

        fetchMany([{
            methodname: 'local_gugrades_release_grades',
            args: {
                courseid: courseid,
                gradeitemid: props.gradeitemid,
                groupid: props.groupid,
                revert: true,
            }
        }])[0]
        .then(() => {
            emit('released');
            showreleasemodal.value = false;
            updateLogo();
            toast.success(mstrings.value['gradesunreleased']);
        })
        .catch((error) => {
            showreleasemodal.value = false;
            debug.value = error;
        });
    }
</script>