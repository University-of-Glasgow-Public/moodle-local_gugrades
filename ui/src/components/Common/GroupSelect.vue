<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div v-if="showgroupselect" class="tw:mt-2">
        <select class="tw:select" @change="group_change($event)">
            <option value="0">{{ mstrings.allparticipants }}</option>
            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
        </select>
    </div>

</template>

<script setup lang="ts">
    import {ref, onMounted, defineEmits, inject} from '@vue/runtime-core';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';

    const groups = ref([]);
    const mstrings = inject('mstrings');
    const showgroupselect = ref(false);
    const debug = ref({});

    const emit = defineEmits(['groupselected']);

    /**
     * Get groups for this course.
     */
    function get_groups() {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        fetchMany([{
            methodname: 'local_gugrades_get_groups',
            args: {
                courseid
            }
        }])[0]
        .then((result) => {
            groups.value = result;
            showgroupselect.value = groups.value.length > 0;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    }

    // Handle change of selection in dropdown.
    function group_change(event) {
        const groupid = event.target.value;
        emit('groupselected', groupid);
    }

    onMounted(() => {
        get_groups();
    });
</script>