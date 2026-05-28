<template>
    <div class="bg-base-100 border border-base-300 rounded-md mt-4 p-6">
        <div class="grid grid-cols-3 max-w-2xl text-sm">
            <div class="col-span-2 font-bold mb-2">Grade category</div>
            <div class="font-bold mb-2">Reasessment</div>
            <CategoryTree :depth="1" :nodes="nodes"></CategoryTree>
        </div>
    </div>
</template>

<script setup lang="ts">
    import CategoryTree from './CategoryTree.vue';

    const props = defineProps<{
        categoryid: number,
        nodes: {
            type: Object,
            required: true
        },
    }>();

</script>