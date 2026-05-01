<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div role="tablist" class="tabs tabs-box text-black">
            <a role="tab" class="tab" :class="{'tab-active': activetab == 'configure'}" @click="clickTab('configure')" @keydown.enter="clickTab('configure')" tabindex="0">{{ mstrings.configure }}</a>
            <a role="tab" class="tab" :class="{'tab-active': activetab == 'capture'}" @click="clickTab('capture')" @keydown.enter="clickTab('capture')" tabindex="0">{{ mstrings.assessmentgradecapture }}</a>
            <a role="tab" class="tab" :class="{'tab-active': activetab == 'conversion'}" @click="clickTab('conversion')" @keydown.enter="clickTab('conversion')" tabindex="0">{{ mstrings.manageconversion }}</a>
            <a role="tab" class="tab" :class="{'tab-active': activetab == 'aggregation'}" @click="clickTab('aggregation')" @keydown.enter="clickTab('aggregation')" tabindex="0">{{ mstrings.coursegradeaggregation }}</a>
            <a role="tab" class="tab" :class="{'tab-active': activetab == 'audit'}" @click="clickTab('audit')"  @keydown.enter="clickTab('audit')" tabindex="0">{{ mstrings.auditlog }}</a>
            <a v-if="settingscapability" role="tab" class="tab" :class="{'tab-active': activetab == 'settings'}" @click="clickTab('settings')"  @keydown.enter="clickTab('settings')" tabindex="0">{{ mstrings.settings }}</a>
        </div>
</template>

<script setup lang="ts">
    import {ref, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';

    const activetab = ref('capture');
    const settingscapability = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    let whichtableft = '';
    let whichtabright = '';

    const props = defineProps({
        viewaggregation: Boolean,
    });

    const toast = useToast();

    const emit = defineEmits(['tabchange']);

    /**
     * Detect change of tab and emit result to parent
     * @param {} item
     */
    function clickTab(item: string) {
        activetab.value = item;
        emit('tabchange', item);
    }

    /**
     * Check capability
     */
     onMounted(() => {

        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:changesettings'
            }
        )
        .then((result: any) => {
            settingscapability.value = result['hascapability'];
            whichtableft = ((settingscapability.value) ? 'settings' : 'audit');
            whichtabright = ((settingscapability.value) ? 'settings' : 'capture');
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });

    });
</script>

<style>
    .navbar-dark .navbar-nav .active > .nav-link {
        font-weight: bold;
        text-decoration: underline;
    }

    .navbar-dark .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.7);
    }
</style>