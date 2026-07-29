<template>
    <div class="mt-8 px-10 flex justify-center-safe gap-4 text-sm text-brand-light-purple">
        <div><a href="https://www.gla.ac.uk">University website</a></div>
        <div aria-hidden="true">•</div>
        <div><a href="https://www.gla.ac.uk/legal/accessibility/statements/moodle">Acessibility</a></div>
        <div aria-hidden="true">•</div>
        <div><a :href="sitebase + 'local/guprivacy/privacy.php'">Privacy</a></div>
        <div aria-hidden="true">•</div>
        <div><a :href="sitebase + 'local/guprivacy/cookies.php'">Cookies</a></div>
        <div aria-hidden="true">•</div>
        <div><a href="#" @click.prevent="reset_tour">Reset user tour on this page</a></div>
        <div aria-hidden="true">•</div>
        <div id="helpwithpage"><a :href="mstrings.lisuurl">Help with this page</a></div>
    </div>
    <div class="mt-4 px-10 flex justify-center-safe gap-8 text-sm text-brand-light-purple">
        The University of Glasgow is a registered Scottish charity: Registration Number SC004401
    </div>
</template>

<script setup lang="ts">
    import { onMounted, ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    const sitebase = ref('');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    onMounted(() => {
        sitebase.value = new URL('../../../../', window.location.href).href;
    })

    function reset_tour() {
        moodleFetch('local_gugrades_set_tour_state', {enabled: true})
        .then(() => {
            window.location.reload()
        })
        .catch((error) => {
            console.error(error);
        });
    }
</script>