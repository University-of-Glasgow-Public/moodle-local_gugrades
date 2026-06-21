<template>
    <!-- Use a simple top-level container div to hold your rows sequentially -->
    <div class="flex flex-col w-full">
        <template v-for="category in props.nodes.categories" :key="category.category.id">
            
            <!-- 
                MAIN ROW WRAPPER:
                Uses flex to organize the columns horizontally and items-center 
                to align text vertically with the switches.
            -->
            <div class="flex items-center justify-between py-2 border-b border-brand-light-purple/10 text-sm text-slate-700">
                
                <!-- COLUMN 1: Tree Spacing Indents & Category Name -->
                <!-- If engineering column is active, give this 50% width, otherwise 75% -->
                <div :class="[props.engineering ? 'w-6/12' : 'w-9/12', 'flex items-center min-w-0 pr-4']">
                    <!-- Recursive indent lines (replaces your old v-for spacer bodge) -->
                    <div 
                        v-for="i in props.depth - 1" 
                        :key="i" 
                        class="w-6 shrink-0 h-5 border-l border-brand-light-purple/40"
                    ></div>
                    
                    <!-- Text truncation prevents long names from pushing switches out of alignment -->
                    <span class="truncate font-medium text-slate-800">
                        {{ category.category.fullname }}
                    </span>
                </div>

                <!-- COLUMN 2: Reassessment Toggle Switch -->
                <!-- Allocates a clean, fixed 3/12 (25%) space block to line up perfectly -->
                <div class="w-3/12 flex items-center justify-start">
                    <Switch />
                </div>

                <!-- COLUMN 3: Optional Engineering Toggle Switch -->
                <!-- Only rendered if your engineering boolean flag returns true -->
                <!-- Also only shown for level 1 categories -->
                <div class="w-3/12 flex items-center justify-start">
                    <Switch v-if="props.engineering  && (depth == 1)" />
                </div>

            </div>

            <!-- Recursive Execution Block (Stepping down into sub-categories) -->
            <CategoryTree 
                v-if="props.depth < 3 && category.categories.length" 
                :nodes="category" 
                :depth="props.depth + 1"
                :engineering="props.engineering"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
    import { onMounted } from 'vue';
    import Switch from '../Tailwind/Switch.vue';
    import type { ICategoryCategory } from '@/js/Interfaces.ts';

    interface IProps {
        nodes: ICategoryCategory;
        depth: number;
        engineering: boolean;
    }

    const props = defineProps< IProps >();

    onMounted(() => {
    });
</script>