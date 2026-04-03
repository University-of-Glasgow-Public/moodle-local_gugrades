<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div role="tablist" class="tw:tabs tw:tabs-box tw:text-black">
            <a role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'configure'}" @click="clickTab('configure')">{{ mstrings.configure }}</a>
            <a role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'capture'}" @click="clickTab('capture')">{{ mstrings.assessmentgradecapture }}</a>
            <a role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'conversion'}" @click="clickTab('conversion')">{{ mstrings.manageconversion }}</a>
            <a role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'aggregation'}" @click="clickTab('aggregation')">{{ mstrings.coursegradeaggregation }}</a>
            <a role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'audit'}" @click="clickTab('audit')">{{ mstrings.auditlog }}</a>
            <a v-if="settingscapability" role="tab" class="tw:tab" :class="{'tw:tab-active': activetab == 'settings'}" @click="clickTab('settings')">{{ mstrings.settings }}</a>
        </div>
</template>

<script setup>
    import {ref, defineEmits, defineProps, inject, onMounted} from '@vue/runtime-core';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';

    const activetab = ref('capture');
    const settingscapability = ref(false);
    const debug = ref({});
    const mstrings = inject('mstrings');
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
    function clickTab(item) {
        activetab.value = item;
        emit('tabchange', item);
    }

    /**
     * Give focus to the element to the left of the current one.
     * Unless of course the user doesn't have permission to do so.
     *
     * @param elemname
     */
    function moveLeft(elemname) {
        let el = '';
        switch (elemname) {
            case 'settings':
                el = whichtableft;
            break;
            case 'aggregation':
                el =  ((props.viewaggregation) ? 'aggregation' : 'conversion');
            break;
            default:
                el = elemname;
            break;
        }
        let tmp = document.getElementsByName(el);
        tmp[0].focus({ focusVisible:true });
    }

    /**
     * Give focus to the element to the right of the current one.
     * Unless of course the user doesn't have permission to do so.
     *
     * @param elemname
     */
    function moveRight(elemname) {
        let el = '';
        switch (elemname) {
            case 'settings':
                el = whichtabright;
            break;
            case 'aggregation':
                el =  ((props.viewaggregation) ? 'aggregation' : 'audit');
            break;
            default:
                el = elemname;
            break;
        }
        let tmp = document.getElementsByName(el);
        tmp[0].focus({ focusVisible:true });
    }

    /**
     * Listen for left/right arrow key events.
     *
     * @param elemname
     * @param e
     */
    function handleKeyNavigation (elemname, e) {
        switch (e.keyCode) {
            case 37:
                moveLeft(elemname);
            break;
            case 39:
                moveRight(elemname);
            break;
      }
    }

    /**
     * Check capability
     */
     onMounted(() => {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        fetchMany([{
            methodname: 'local_gugrades_has_capability',
            args: {
                courseid: courseid,
                capability: 'local/gugrades:changesettings'
            }
        }])[0]
        .then((result) => {
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