<template>
    <!-- Use a simple top-level container div to hold your rows sequentially -->
    <div class="flex flex-col w-full">
        <template v-for="category in props.nodes.categories" :key="category.category.id">
            
            <!-- 
                MAIN ROW WRAPPER:
                Items align naturally inside their explicit percentage tracking slots.
            -->
            <div class="flex items-center py-2 border-b border-brand-light-purple/10 text-sm text-slate-700">
                
                <!-- COLUMN 1: Tree Spacing Indents & Category Name -->
                <!-- FIXED: Added 'flex items-center min-w-0' to force side-by-side layout -->
                <div class="flex-1 flex items-center min-w-0 pr-4">
                    <!-- Recursive indent lines (Renders horizontally now) -->
                    <div 
                        v-for="i in props.depth - 1" 
                        :key="i" 
                        class="w-6 shrink-0 h-5 border-l border-brand-light-purple/40"
                    ></div>
                    
                    <!-- Text node label stays beautifully in-line next to the indents -->
                    <span class="truncate font-medium text-slate-800">
                        {{ category.category.fullname }}
                    </span>
                </div>

                <!-- COLUMN 2: Reassessment Toggle Switch -->
                <!-- Occupies its fixed track comfortably with left alignment -->
                <div class="w-52 shrink-0 flex items-center justify-start pr-2">
                    <Switch label="Reassessment?"/>
                </div>

                <!-- COLUMN 3: Optional Engineering Toggle Switch -->
                <!-- 
                  FIXED: Parent track wraps in props.engineering so it completely vanishes 
                  when the parent component turns engineering support off.
                -->
                <div v-if="props.engineering" class="w-52 shrink-0 flex items-center justify-start pr-2">
                    <Switch v-if="props.depth === 1" @change="eng_change($event, category.category.id)" label="Exam?"/>
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

    /**
     * Engineering switch has been changed
     */
    function eng_change(state: boolean|string, categoryid: number) {
        console.log(state);
        console.log(categoryid);
    }

    onMounted(() => {
    });
</script>