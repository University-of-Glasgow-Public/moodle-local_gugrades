<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="my-5">
        <EasyDataTable
            alternating
            :headers="headers"
            :items="items"
            table-class-name="audit-table"
        >
        </EasyDataTable>
    </div>
    <TwButton class="mt-2" color="success" @click="download_clicked">{{ mstrings.downloadtocsv }}</TwButton>
</template>

<script setup lang="ts">
    import {ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { saveAs } from 'file-saver';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { IAuditItem } from '@/js/Interfaces';
    import type { Header } from "vue3-easy-data-table";
    import TwButton from '@/components/Tailwind/TwButton.vue';
    import TablePagination from '@/components/Common/TablePagination.vue';

    const items = ref< IAuditItem[] >([]);
    const headers = ref< Header[] >([]);
    const debug = ref({});
    const loaded = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Download button clicked
     */
    function download_clicked() {
        let csv =
            mstringstore.getMstring('time') + ', ' +
            mstringstore.getMstring('gradeitem') + ', ' +
            mstringstore.getMstring('by') + ', ' +
            mstringstore.getMstring('relateduser') + ', ' +
            mstringstore.getMstring('message') + '\n';
        items.value.forEach((item) => {
            csv +=
                '"' + item.time + '", ' +
                '"' + item.gradeitem + '", ' +
                '"' + item.username + '", ' +
                '"' + item.relatedusername + '", ' +
                '"' + item.message.replaceAll('"', '') + '"\n';
        });
        const d = new Date();
        const filename = 'Audit_' + d.toLocaleString() + '.csv';
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
        saveAs(blob, filename);
    }

    onMounted(() => {
        headers.value = [
               {text: mstringstore.getMstring('time'), value: 'time'},
               {text: mstringstore.getMstring('gradeitem'), value: 'gradeitem'},
               {text: mstringstore.getMstring('by'), value: 'username'},
               {text: mstringstore.getMstring('relateduser'), value: 'relatedusername'},
               {text: mstringstore.getMstring('message'), value: 'message'},
            ];

        moodleFetch(
            'local_gugrades_get_audit',
            {}
        )
        .then((result: any) => {
            items.value = result;
            loaded.value = true;

        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    });
</script>

<style>
    .audit-table {
        --easy-table-header-background-color: var(--color-primary);
        --easy-table-header-font-color: var(--color-primary-content);

        --easy-table-body-row-background-color: var(--color-base-100);
        --easy-table-body-row-font-color: var(--color-base-content);

        --easy-table-body-even-row-background-color: var(--color-base-300);
        --easy-table-body-even-row-font-color: var(--color-base-content);
    }
</style>