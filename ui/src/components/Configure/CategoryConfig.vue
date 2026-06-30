<template>
    <div class="bg-slate-100 border border-slate-300 rounded-md mt-4 p-6">
        <UAlert variant="info" class="mb-5">{{ mstrings.changessaved }}</UAlert>
        <div class="max-w-2xl">
            <div class="flex items-center text-xs font-bold uppercase tracking-wider text-brand-dark-purple/70 pb-3 border-b border-brand-light-purple/20">
                <!-- COLUMN 1 HEADER: Matches row w-6/12 or w-9/12 boundaries -->
                <div class="flex-1 pr-4">
                    {{ mstrings.gradecategory }}
                </div>
                
                <!-- COLUMN 2 HEADER: Matches row w-3/12 layout track exactly -->
                <div class="w-52 shrink-0 pr-2">
                    {{ mstrings.reassessment }}
                </div>   
                
                <!-- COLUMN 3 HEADER: Matches row w-3/12 layout track exactly -->
                <div v-if="engineering" class="w-52 shrink-0 pr-2">
                    Final exam / classwork (Engineering)
                </div>        
            </div>
            <CategoryTree :depth="1" :nodes="nodes" :engineering="engineering"></CategoryTree>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import CategoryTree from './CategoryTree.vue';
    import UAlert from '../Common/UAlert.vue';
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