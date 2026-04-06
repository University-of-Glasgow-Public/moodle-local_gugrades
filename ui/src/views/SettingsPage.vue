<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div>
        <FormKit type="form" @submit="submit_form" class="tw:mt-8">

            <div v-if="!gradesreleased" class="alert alert-warning">
                {{ mstrings.gradesnotreleased }}
            </div>

            <FormKit
                type="checkbox"
                :label="mstrings.disabledashboard"
                :disabled="!gradesreleased"
                v-model="disabledashboard"
                >
            </FormKit>

        </FormKit>

        <div class="mt-5">
            <ResetButton></ResetButton>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, inject, onMounted} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import ResetButton from '@/components/Settings/ResetButton.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useLogo } from '@/js/monochromelogo';
    import type { ISetting } from '@/js/Interfaces';

    const disabledashboard = ref(false);
    const debug = ref({});
    const gradesreleased = ref(true);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();
    const { updateLogo } = useLogo();

    /**
     * Submit button clicked
     */
    function submit_form() {

        moodleFetch(
            'local_gugrades_save_settings',
            {
                gradeitemid: 0,
                settings: [
                    {
                        name: 'disabledashboard',
                        value: disabledashboard.value,
                    },
                ]
            }
        )
        .then(() => {
            updateLogo();
            toast.success(mstringstore.getMstring('settingssaved'));
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Load initial page
     */
    onMounted(() => {

        updateLogo();

        moodleFetch(
            'local_gugrades_get_settings',
            {
                gradeitemid: 0,
            }
        )
        .then((result: any) => {
            const settings: ISetting[] = result;
            settings.forEach((setting) => {

                // TODO: Something a bit cleverer than this
                if (setting.name == 'disabledashboard') {
                    disabledashboard.value = setting.value ? true : false;
                }
            })
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    })

</script>