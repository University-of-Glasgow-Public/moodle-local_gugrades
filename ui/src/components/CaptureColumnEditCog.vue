<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a class="tw:ml-1 tw:tooltip" aria-label="Bulk edit" data-tip="Bulk edit" @click.prevent="cog_clicked"><Cog6ToothIcon class="tw:size-6 tw:text-yellow-500"></Cog6ToothIcon></a>
</template>

<script setup lang="ts">
    import {inject, ref} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import { useToast } from "vue-toastification";
    import { Cog6ToothIcon } from '@heroicons/vue/24/outline';

    const props = defineProps({
        itemid: {
            type: Number,
            default: 0
        },
        header: {
            type: Object,
            default: {}
        },
        active: Boolean,
    });

    const toast = useToast();
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['editcolumn']);

    /**
     * Cog wheel has been clicked
     */
    function cog_clicked() {

        moodleFetch(
            'local_gugrades_get_capture_cell_form',
            {
                gradeitemid: props.itemid,
            }
        )
        .then((result: any) => {
            const usescale = result.usescale;
            const grademax = result.grademax;
            const scalemenu = result.scalemenu;
            const adminmenu = result.adminmenu;

            // Add 'use grade' option onto front of adminmenu
            adminmenu.unshift({
                value: 'GRADE',
                label: mstringstore.getMstring('selectnormalgradeshort'),
            });

            // send all this stuff back
            emits('editcolumn', {
                columnname: props.header.value,
                gradetype: props.header.gradetype,
                other: props.header.other,
                columnid: props.header.columnid,
                usescale: usescale,
                grademax: grademax,
                scalemenu: scalemenu,
                adminmenu: adminmenu,
                notes: '',
            });
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }
</script>