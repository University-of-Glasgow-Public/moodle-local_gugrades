<template>
    <div class="bg-slate-100 border border-slate-300 rounded-md mt-4 p-6">
        <div class="max-w-2xl">
            <div class="flex justify-between items-center text-xs font-bold uppercase tracking-wider text-brand-dark-purple/70 pb-3 border-b border-brand-light-purple/20">
                <div>{{ mstrings.gradecategory }}</div>
                <div>{{ mstrings.reassessment }}</div>   
                <div v-if="engineering">Final exam / classwork (Engineering)</div>        
            </div>
            <CategoryTree :depth="1" :nodes="nodes" :engineering="engineering"></CategoryTree>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import CategoryTree from './CategoryTree.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { ICategoryCategory } from '@/js/Interfaces.ts';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    interface IProps {
        categoryid: number;
        nodes: ICategoryCategory;
        engineering: boolean;
    }

    const props = defineProps<IProps>();

</script>