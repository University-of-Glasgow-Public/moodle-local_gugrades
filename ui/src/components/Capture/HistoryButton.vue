<template>
    <DebugDisplay :debug="debug"></DebugDisplay>
    <a @click.prevent="read_history()" class="block px-3 py-2.5 text-sm cursor-pointer transition-colors hover:bg-university-blue/10">{{ mstrings.history }}</a>

    <VueModal v-model="showhistorymodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.gradehistory">
        <div>
            <ul class="list-none">
                <li><b>{{ mstrings.name }}:</b> {{ props.name }}</li>
                <li><b>{{ mstrings.itemname }}:</b> {{ props.itemname }}</li>
            </ul>
        </div>

        <UAlert v-if="grades.length == 0">{{ mstrings.nohistory }}</UAlert>


        <UTable :data="grades" :columns="columns" :dense="true" :filterable="false" class="mt-3"></UTable>

        <div class="flex justify-end mt-5">
            <UButton variant="warning" @click="closemodal">{{ mstrings.close }}</UButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref, onMounted, h } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import UTable from '../Common/UTable.vue';
    import { createColumnHelper } from '@tanstack/vue-table';
    import GradeColor from '../Common/GradeColor.vue';
    import type { IHistory } from '@/js/Interfaces.ts';

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
        close: Function,
    });

    const columnHelper = createColumnHelper< IHistory >();
    const columns = [
        columnHelper.accessor('time', {
            header: mstringstore.getMstring('time'),
        }),

        columnHelper.accessor('auditbyname', {
            header: mstringstore.getMstring('by'),
        }),

        columnHelper.accessor('displaygrade', {
            header: mstringstore.getMstring('grade'),
            cell: (info) => {
                const grade = info.getValue();
                return h(GradeColor, {
                    grade: grade,
                });
            }
        }),

        columnHelper.accessor('description', {
            header: mstringstore.getMstring('gradetype'),
        }),

        columnHelper.accessor('current', {
            header: mstringstore.getMstring('current'),
        }),

        columnHelper.accessor('auditcomment', {
            header:  mstringstore.getMstring('comment'),
        }),
    ];

    /**
     * Close modal and whatever called it
     */
    function closemodal() {
        showhistorymodal.value = false;
        if (props.close) {
            props.close();
        }
    }

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
</script>
