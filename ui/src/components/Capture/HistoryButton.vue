<template>
    <DebugDisplay :debug="debug"></DebugDisplay>
    <a @click.prevent="read_history()">{{ mstrings.history }}</a>

    <VueModal v-model="showhistorymodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings.gradehistory">
        <div>
            <ul class="tw:list-none">
                <li><b>{{ mstrings.name }}:</b> {{ props.name }}</li>
                <li><b>{{ mstrings.itemname }}:</b> {{ props.itemname }}</li>
            </ul>
        </div>

        <TwAlert v-if="grades.length == 0">{{ mstrings.nohistory }}</TwAlert>

        <EasyDataTable v-else :headers="headers" :items="grades">
        </EasyDataTable>

        <div class="tw:flex tw:justify-end tw:mt-5">
            <TwButton color="warning" @click="showhistorymodal = false">{{ mstrings.close }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, onMounted, inject} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import TwButton from '../Tailwind/TwButton.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';

    interface IHeader {
        text: string;
        value: string;
    }

    const showhistorymodal = ref(false);
    const grades = ref([]);
    const headers = ref< IHeader[] >([]);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const props = defineProps({
        userid: Number,
        itemid: Number,
        name: String,
        itemname: String,
    });

    /**
     * Read history on button click
     */
    function read_history() {
        moodleFetch(
            'local_gugrades_get_history',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
            }
        )
        .then((result: any) => {
            grades.value = result;
        })
        .catch((error) => {
            window.console.error(error);
            showhistorymodal.value = false;
            debug.value = error;
        });

        showhistorymodal.value = true;
    }

    /**
     * Setup the table.
     */
    onMounted(() => {
        headers.value = [
               {text: mstringstore.getMstring('time'), value: 'time'},
               {text: mstringstore.getMstring('by'), value: 'auditbyname'},
               {text: mstringstore.getMstring('grade'), value: 'displaygrade'},
               {text: mstringstore.getMstring('gradetype'), value: 'description'},
               {text: mstringstore.getMstring('current'), value: 'current'},
               {text: mstringstore.getMstring('comment'), value: 'auditcomment'},
            ];
    });
</script>
