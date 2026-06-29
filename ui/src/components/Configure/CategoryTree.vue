<template>
    <!-- Use a simple top-level container div to hold your rows sequentially -->
    <div class="flex flex-col w-full">

        <UAlert variant="error" v-if="hasNoCategories">No categories available to select.</UAlert>

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
                        class="w-6 shrink-0 h-5 border-l-2 border-brand-light-purple/80"
                    ></div>
                    
                    <!-- Text node label stays beautifully in-line next to the indents -->
                    <span class="truncate font-medium text-slate-800">
                        {{ category.category.fullname }}
                    </span>
                </div>

                <!-- COLUMN 2: Reassessment Toggle Switch -->
                <div class="w-52 shrink-0 flex items-center justify-start pr-2">
                    <Switch 
                        :label="mstrings.reassessment + '?'"
                        @change="reassess_change($event, category.category.id)"
                        :disabled="props.disablereassess"
                    />
                </div>

                <!-- COLUMN 3: Optional Engineering Toggle Switch -->
                <div v-if="props.engineering" class="w-52 shrink-0 flex items-center justify-start pr-2">
                    <Switch
                        v-if="props.depth === 1" 
                        @change="eng_change($event, category.category.id)" 
                        :label="mstrings.exam + '?'"
                        :active="engineeringcats.some(id => id == category.category.id)"
                    />
                </div>

            </div>

            <!-- Recursive Execution Block (Stepping down into sub-categories) -->
            <CategoryTree 
                v-if="props.depth < 3 && category.categories.length" 
                :nodes="category" 
                :depth="props.depth + 1"
                :engineering="props.engineering"
                :disablereassess="reassesscats.includes(Number(category.category.id)) || props.disablereassess"
            />
        </template>
    </div>
</template>


<script setup lang="ts">
    import { onMounted, ref, computed } from 'vue';
    import Switch from '../Tailwind/Switch.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { ICategoryCategory, IFlag } from '@/js/Interfaces.ts';
    import UAlert from '../Common/UAlert.vue';

    interface IProps {
        nodes: ICategoryCategory;
        depth: number;
        engineering: boolean;
        disablereassess: boolean;
    }

    const props = defineProps< IProps >();

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const engineeringcats = ref< number[] >([]);
    const reassesscats = ref< number[] >([]);

    /**
     * Switch clicked.
     * @param state 
     * @param categoryid 
     */
    function eng_change(state: boolean | string, categoryid: number) {

        engineeringcats.value = engineeringcats.value.filter(id => id != categoryid);

        if (state === 'on' || state === true) {
            // Enforce pure integer values inside your tracking arrays
            engineeringcats.value.push(Number(categoryid));
        }

        // Load into flags array 
        const flags = engineeringcats.value.map((id) => ({
            gradecategoryid: id,
            gradeitemid: 0,
            engexam: true,
            resit: false,
        }));
        
        // Push results to Moodle
        moodleFetch('local_gugrades_write_flags', {
            flags: flags,
        })
        .catch((error) => {
            console.error(error);
        });
    }

    /**
     * Reassessment change
     */
    function reassess_change(state: boolean | string, categoryid: number) {

        // If this is selected, then 
        reassesscats.value = reassesscats.value.filter(id => id != categoryid);

        if (state === 'on' || state === true) {
            // Enforce pure integer values inside your tracking arrays
            reassesscats.value.push(Number(categoryid));
        }

        console.log(reassesscats.value);

    }

    const hasNoCategories = computed(() => {
        return !props.nodes.categories || props.nodes.categories.length === 0;
    });

    onMounted(() => {
        moodleFetch('local_gugrades_read_flags', {})
        .then((result: any) => {
            if (result && result.flags) {
                const activeIds = result.flags
                    .filter((flag: IFlag) => flag.gradecategoryid)
                    .map((flag: IFlag) => flag.gradecategoryid);

                // Overwriting the whole value at once guarantees Vue triggers a full re-render!
                engineeringcats.value = activeIds;
            }
        })
        .catch((error) => {
            console.error(error);
        });
    });
</script>