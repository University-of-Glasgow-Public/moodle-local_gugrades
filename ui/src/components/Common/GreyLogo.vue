<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div v-if="!loading" class="flex justify-center">
        <img :src="url" id="mygradeslogo" alt="MyGrades Logo" class="border-2 rounded-2xl border-gray-300 px-5 bg-white" :class="{'grayscale': monochrome}"/>
    </div>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useLogo } from '@/js/monochromelogo.js';

    const debug = ref({});
    const url = ref('');
    const loading = ref(true);

    const {monochrome, updateLogo} = useLogo();

    function get_url() {

        const images = [
            {
                imagename: 'MyGradesLogoSmall',
                component: 'local_gugrades',
            }
        ];

        moodleFetch(
            'local_gugrades_get_image_urls',
            {
                images: images,
            }
        )
        .then((result: any) => {
            url.value = result[0]['url'];
            loading.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    onMounted(() => {
        get_url();
        updateLogo();
    });
</script>

<style>
    .monoimage {
        filter: grayscale(100);
    }
</style>