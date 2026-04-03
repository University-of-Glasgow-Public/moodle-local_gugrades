<template>
    <ul class="tw:list-none tw:pl-8">
        <li v-for="item in props.nodes.items" :key="item.id">
            <a href="#" @click.prevent="activity_click(item.id)">{{ item.itemname }}</a>
        </li>
        <li v-for="category in props.nodes.categories" :key="category.id">
            <div class="tw:font-bold tw:flex tw:justify-start tw:space-x-4">
                <FolderIcon v-if="props.depth == 1" class="tw:size-5 tw:text-black-500 tw:mr-2"></FolderIcon>
                <FolderOpenIcon v-else class="tw:size-5 tw:text-black-500 tw:mr-2"></FolderOpenIcon>
                {{ category.category.fullname }}
            </div>
            <ActivityTree :nodes="category" @activityselected="sub_activity_click" :depth="props.depth + 1"></ActivityTree>
        </li>
    </ul>
</template>

<script setup lang="ts">
    import { FolderIcon, FolderOpenIcon } from '@heroicons/vue/24/outline';

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