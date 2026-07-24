<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="my-5">
        <UTable :data="items" :columns="columns" :sortable="false" class="mt-3"></UTable>
    </div>
    <UButton class="mt-2" variant="success" @click="download_clicked">{{ mstrings.downloadtocsv }}</UButton>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { saveAs } from 'file-saver';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { IAuditItem } from '@/js/Interfaces';
    import UTable from '@/components/Common/UTable.vue';
    import UButton from '@/components/Common/UButton.vue';
    import { createColumnHelper } from '@tanstack/vue-table';

    const items = ref< IAuditItem[] >([]);
    const debug = ref({});
    const loaded = ref(false);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );


    const columnHelper = createColumnHelper< IAuditItem >();
    const columns = [
        columnHelper.accessor('time', {
            header: mstringstore.getMstring('time'),
        }),

        columnHelper.accessor('gradeitem', {
            header: mstringstore.getMstring('gradeitem'),
        }),

        columnHelper.accessor('username', {
            header: mstringstore.getMstring('by'),
        }),

        columnHelper.accessor('relatedusername', {
            header: mstringstore.getMstring('relateduser'),
        }),

        columnHelper.accessor('message', {
            header: mstringstore.getMstring('message'),
        }),
    ];

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
