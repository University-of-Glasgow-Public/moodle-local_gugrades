<template>
    <ul class="list-none pl-8">
        <li v-for="item in props.nodes.items" :key="item.id">
            <a href="#" @click.prevent="activity_click(item.id)">{{ item.itemname }}</a>
        </li>
        <li v-for="category in props.nodes.categories" :key="category.category.id">
            <button
                type="button"
                class="font-bold flex items-center text-left w-full bg-transparent border-0 p-0 cursor-pointer"
                :aria-expanded="is_expanded(category.category.id)"
                :aria-controls="'activitytree-' + category.category.id"
                @click="toggle(category.category.id)"
            >
                <ChevronDown v-if="is_expanded(category.category.id)" :size="16" class="mr-1 shrink-0" aria-hidden="true"></ChevronDown>
                <ChevronRight v-else :size="16" class="mr-1 shrink-0" aria-hidden="true"></ChevronRight>
                <FolderOpen v-if="is_expanded(category.category.id)" :size="18" class="mr-2 shrink-0" aria-hidden="true"></FolderOpen>
                <Folder v-else :size="18" class="mr-2 shrink-0" aria-hidden="true"></Folder>
                {{ category.category.fullname }}
            </button>
            <ActivityTree
                v-show="is_expanded(category.category.id)"
                :id="'activitytree-' + category.category.id"
                :nodes="category"
                @activityselected="sub_activity_click"
                :depth="props.depth + 1"
                :openall="openall"
            ></ActivityTree>
        </li>
    </ul>
</template>

<script setup lang="ts">
    import { ref, computed } from 'vue';
    import { FolderOpen, Folder, ChevronDown, ChevronRight } from '@lucide/vue';

    // Trees with fewer assessments than this start fully expanded.
    const OPEN_ALL_LIMIT = 20;

    const props = defineProps({
        nodes: {
            type: Object,
            required: true
        },
        depth: {
            type: Number,
            required: true
        },
        // Set by the top-level tree and passed down to sub-trees.
        openall: {
            type: Boolean,
            default: undefined
        }
    });

    const emit = defineEmits(['activityselected']);

    function count_items(node: any): number {
        const items = node.items ? node.items.length : 0;
        const categories = node.categories || [];
        return categories.reduce((total: number, child: any) => total + count_items(child), items);
    }

    const openall = computed(() => props.openall ?? count_items(props.nodes) < OPEN_ALL_LIMIT);

    // Explicit user choices; categories without one follow the openall default.
    const expanded = ref<Record<number, boolean>>({});

    function is_expanded(categoryid: number): boolean {
        return expanded.value[categoryid] ?? openall.value;
    }

    function toggle(categoryid: number) {
        expanded.value[categoryid] = !is_expanded(categoryid);
    }

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