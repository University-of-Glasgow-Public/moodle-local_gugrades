<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div v-if="showgroupselect">
        <!-- Matching Label to keep typography uniform -->
        <div class="text-sm font-bold mb-1 opacity-70">GROUP</div>
        
        <!-- Matching visual properties: solid white, slate border, and matching drop-shadow -->
        <select 
            class="select select-bordered bg-white text-neutral border-slate-300 w-120 shadow-md focus:outline-none focus:border-primary" 
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