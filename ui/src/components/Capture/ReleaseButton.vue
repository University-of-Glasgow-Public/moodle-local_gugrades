<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <MenuButton @click="release_button_clicked" :disabled="!enable" iconName="LockKeyholeOpen">
        <span v-if="props.released">
            <span v-if="grouprelease">{{ mstrings['unreleasegradesgroup'] }}</span>
            <span v-else>{{ mstrings['unreleasegrades'] }}</span>
        </span>
        <span v-if="!props.released">
            <span v-if="grouprelease">{{ mstrings['releasegradesgroup'] }}</span>
            <span v-else>{{ mstrings['releasegrades'] }}</span>
        </span>
    </MenuButton>

    <VueModal v-model="showreleasemodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings['releasegrades']">

        <div v-if="loading">
            <PleaseWait></PleaseWait>
        </div>

        <!-- Show if NOT already released -->
        <div v-if="!props.released">
            <UAlert v-if="!props.released" variant="info">
                {{ mstrings.releaseconfirm }}
                <p v-if="grouprelease" class="mt-1"><b>{{ mstrings.releaseconfirmgroup }}</b></p>
            </UAlert>

            <UAlert v-if="props.released" variant="info">
                {{ mstrings.releaseconfirmstern }}
                <p v-if="grouprelease" class="mt-1"><b>{{ mstrings.releaseconfirmgroup }}</b></p>
            </UAlert>

            <div class="mt-5 flex gap-2 justify-start">
                <UButton variant="primary" @click="release_grades">{{ mstrings.yesrelease }}</UButton>
                <UButton variant="warning" @click="showreleasemodal = false">{{ mstrings.cancel }}</UButton>
            </div>
        </div>

        <!-- Show if already released -->
        <div v-if="props.released">
            <h4 class="font-bold mb-2">Revert release of grades</h4>
            <UAlert variant="info">
                {{ mstrings.removerelease }}
                <p v-if="grouprelease" class="mt-2"><b>{{ mstrings.removereleasegroup }}</b></p>
            </UAlert>

            <div class="mt-5 flex gap-2 justify-start">
                <UButton variant="primary" @click="revert_release">{{ mstrings.yesunrelease }}</UButton>
                <UButton variant="warning" @click="showreleasemodal = false">{{ mstrings.cancel }}</UButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import MenuButton from '../Common/MenuButton.vue';
    import { useLogo } from '@/js/monochromelogo';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from '../Common/UAlert.vue';
    import UButton from '../Common/UButton.vue';

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
        groupid: {
            type: Number,
            default: 0,
        },
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
            toast.success(mstrings.value['gradesreleased']);
        })
        .catch((error) => {
            console.error(error);
            showreleasemodal.value = false;
            debug.value = error;
        });
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
            toast.success(mstrings.value['gradesunreleased']);
        })
        .catch((error) => {
            showreleasemodal.value = false;
            debug.value = error;
        });
    }
</script>