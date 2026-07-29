<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div v-if="showgroupselect">
        <!-- Matching Label to keep typography uniform -->
        <div class="text-sm font-bold mb-1 opacity-70">GROUP</div>
        
        <!-- Matching visual properties: solid white, slate border, and matching drop-shadow -->
        <select 
            class="appearance-none px-3 py-2 pr-8 rounded-md bg-white text-brand-dark-purple border border-slate-300 w-120 shadow-md focus:outline-none focus:border-university-blue bg-no-repeat" 
            style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236b7280%22 stroke-width=%222%22%3E%3Cpath d=%22M6 9l6 6 6-6%22/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-size: 16px;"
            v-model="groupid" 
            @change="handleUserSelection"
            aria-label="Group select"
        >
            <option value="0">{{ mstrings.allparticipants }}</option>
            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
        </select>
    </div>

</template>

<script setup lang="ts">
    import {ref, onMounted, watch} from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useMstrings } from '@/stores/mstrings.js';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { IGroup } from '@/js/Interfaces';

    const groups = ref< IGroup[] >([]);
    const groupid = ref(0);
    const showgroupselect = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits(['groupselected']);

    /**
     * Get groups for this course.
     */
    function get_groups() {

        moodleFetch(
            'local_gugrades_get_groups',
            {}
        )
        .then((result: any) => {
            groups.value = result;
            showgroupselect.value = groups.value.length > 0;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    }

    // Handle change of selection in dropdown.
    /*
    watch(groupid, () => {
        emit('groupselected', groupid.value);
    })
    */

    function handleUserSelection() {
        emit('groupselected', groupid.value);
    }

    onMounted(() => {
        get_groups();
    });
</script>