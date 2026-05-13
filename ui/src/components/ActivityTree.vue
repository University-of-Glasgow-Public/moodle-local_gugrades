<template>
    <ul class="list-none pl-8">
        <li v-for="item in props.nodes.items" :key="item.id">
            <a href="#" @click.prevent="activity_click(item.id)">{{ item.itemname }}</a>
        </li>
        <li v-for="category in props.nodes.categories" :key="category.id">
            <div class="font-bold flex justify-start space-x-4">
                <Folder v-if="props.depth == 1" :size="18" class="mr-2"></Folder>
                <FolderOpen v-else :size="18" class="mr-2"></FolderOpen>
                {{ category.category.fullname }}
            </div>
            <ActivityTree :nodes="category" @activityselected="sub_activity_click" :depth="props.depth + 1"></ActivityTree>
        </li>
    </ul>
</template>

<script setup lang="ts">
    import { FolderOpen, Folder } from '@lucide/vue';

    const props = defineProps({
        nodes: {
            type: Object,
            required: true
        },
        depth: {
            type: Number,
            required: true
        }
    });

    const emit = defineEmits(['activityselected']);

    // Emit activity id when activity selected
    function activity_click(itemid: number) {
        emit('activityselected', itemid);
    }

    // As emit only works for one level, this re-emits events
    // from lower levels.
    function sub_activity_click(activityid: number) {
        emit('activityselected', activityid);
    }
</script>