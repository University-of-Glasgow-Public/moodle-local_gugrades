<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" :disabled="props.disabled" @click="showreleasemodal=true">
            <span v-if="grouprelease"><span v-if="props.released">(Un-)</span>{{ mstrings.releaseaggregatedgroup }}</span>
            <span v-else><span v-if="props.released">(Un-)</span>{{ mstrings.releaseaggregatedgrade }}</span>
    </TwButton>

    <VueModal v-model="showreleasemodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings.releaseaggregatedgrade">

        <PleaseWait v-if="loading"></PleaseWait>

        <!-- Displayed if not released -->
        <div v-if="!props.released">
            <h4>{{ mstrings.releaseaggregatedgrade }}</h4>
            <TwAlert v-if="!props.released" color="warning" class="tw:mt-2">
                <div>
                    {{ mstrings.releaseaggregatedconfirm }}
                    <p v-if="grouprelease" class="mt-1"><b>{{ mstrings.releaseconfirmgroup }}</b></p>
                </div>
            </TwAlert>
            <TwAlert v-if="props.released" color="error" class="tw:mt-2">
                <div>
                    {{ mstrings.releaseaggregatedconfirmstern }}
                    <p v-if="grouprelease" class="mt-1"><b>{{ mstrings.releaseconfirmgroup }}</b></p>
                </div>
            </TwAlert>
            <div class="tw:mt-4">
                <TwButton color="primary" @click="release_grades()">{{ mstrings.yesrelease }}</TwButton>
                <TwButton color="warning" @click="showreleasemodal = false">{{ mstrings.cancel }}</TwButton>
            </div>
        </div>

        <!-- display if already released -->
        <div v-if="props.released" class="tw:mt-4">
            <h4>Revert release of grades</h4>
            <TwAlert color="error" class="tw:mt-2">
                <div>
                    {{ mstrings.removerelease }}
                    <p v-if="grouprelease" class="mt-1"><b>{{ mstrings.removereleasegroup }}</b></p>
                </div>
            </TwAlert>
            <div class="tw:mt-4">
                <TwButton class="error" @click="revert_release()">{{ mstrings.yesunrelease }}</TwButton>
                <TwButton color="warning" @click="showreleasemodal = false">{{ mstrings.cancel }}</TwButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import { useLogo } from '@/js/monochromelogo';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';

    const showreleasemodal = ref(false);
    const loading = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits(['released']);

    const toast = useToast();

    const { updateLogo } = useLogo();

    const props = defineProps({
        gradeitemid: Number,
        groupid: {
            type: Number,
            required: true
        },
        released: Boolean,
        disabled: Boolean,
    });

    const grouprelease = computed(() => {
        return props.groupid > 0;
    });

    /**
     * Release grades on button click
     */
    function release_grades() {

        loading.value = true;

        moodleFetch(
            'local_gugrades_release_grades',
            {
                gradeitemid: props.gradeitemid,
                groupid: props.groupid,
                revert: false,
            }
        )
        .then(() => {
            emit('released');
            showreleasemodal.value = false;
            updateLogo();
            loading.value = false;
            toast.success(mstringstore.getMstring('gradesreleased'));
        })
        .catch((error) => {
            window.console.error(error);
            showreleasemodal.value = false;
            debug.value = error;
        });

        showreleasemodal.value = true;
    }

    /**
     * Revert release grades on button click
     */
     function revert_release() {

        loading.value = true;

        moodleFetch(
            'local_gugrades_release_grades',
            {
                gradeitemid: props.gradeitemid,
                groupid: props.groupid,
                revert: true,
            }
        )
        .then(() => {
            emit('released');
            showreleasemodal.value = false;
            updateLogo();
            loading.value = false;
            toast.success(mstringstore.getMstring('gradesunreleased'));
        })
        .catch((error) => {
            showreleasemodal.value = false;
            debug.value = error;
        });

        showreleasemodal.value = true;
    }
</script>