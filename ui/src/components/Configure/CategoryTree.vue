<template>
    <template v-for="category in nodes.categories">
        <div class="col-span-2 flex">

            <!-- add padding -->
            <!-- first one just to force some extra vertical space -->
            <div class="py-1">&nbsp;</div>
            <div v-for="i in depth - 1" class="w-8 border-l"></div>

            {{ category.category.fullname }}
        </div>
        <div class="">
            <Switch></Switch>
        </div>
        <CategoryTree v-if="depth < 3" :nodes="category" :depth="depth + 1"></CategoryTree>
    </template>
</template>

<script setup lang="ts">
    import { onMounted } from 'vue';
    import Switch from '../Tailwind/Switch.vue';

    const props = defineProps({
        nodes: {
            type: Object,
            required: true
        },
        depth: {
            type: Number,
            required: true
        },
    });

    onMounted(() => {
    });
</script>