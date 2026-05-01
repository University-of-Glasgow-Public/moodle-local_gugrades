<template>
    <div class="w-full">
        <LevelOneSelect  @levelchange="levelOneChange"></LevelOneSelect>
        <div>
            <ActivitySelect v-if="showactivityselect" :categoryid="level1category" :currentitem="itemid" @activityselected="activity_selected"></ActivitySelect>
        </div>
        <GroupSelect v-if="itemid" @groupselected="groupselected"></GroupSelect>
    </div>
</template>

<script setup lang="ts">
    import {ref } from 'vue';
    import LevelOneSelect from '@/components/Common/LevelOneSelect.vue';
    import ActivitySelect from '@/components/Capture/ActivitySelect.vue';
    import GroupSelect from '@/components/Common/GroupSelect.vue'
    import type { IEmitItemData } from '@/js/Interfaces';

    const level1category = ref(0);
    const showactivityselect = ref(false);
    const itemid = ref(0);
    const groupid = ref(0);

    const emits = defineEmits<{
        selecteditemid: [payload: IEmitItemData]
    }>();

    /**
     * Emit the current data
     */
    function emitdata() {
        const itemdata: IEmitItemData = {
            itemid: itemid.value,
            groupid: groupid.value,
            categoryid: level1category.value,
        };
        emits('selecteditemid', itemdata );
    }

    /**
     * Capture change to top level category dropdown
     * @param {*} level
     */
     function levelOneChange(level: number) {
        itemid.value = 0;
        level1category.value = level;
        if (level1category.value) {
            showactivityselect.value = true;
        } else {
            showactivityselect.value = false;
        }

        emitdata();
    }

    /**
     * Capture change to activity selection
     */
     function activity_selected(newitemid: number) {
        itemid.value = newitemid;
        emitdata();
    }

    /**
     * Capture change to group
     */
    function groupselected(gid: number) {
        groupid.value = gid;
        emitdata();
    }
</script>