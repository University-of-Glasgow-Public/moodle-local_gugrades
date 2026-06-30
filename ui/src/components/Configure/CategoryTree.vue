<template>
    <div class="flex flex-col w-full">

        <UAlert variant="error" v-if="hasNoCategories">No categories available to select.</UAlert>

        <template v-for="category in props.nodes.categories" :key="category.category.id">
            
            <div class="flex items-center py-2 border-b border-brand-light-purple/10 text-sm text-slate-700">
                
                <!-- COLUMN 1: Tree Spacing Indents & Category Name -->
                <div class="flex-1 flex items-center min-w-0 pr-4">
                    <div 
                        v-for="i in props.depth - 1" 
                        :key="i" 
                        class="w-6 shrink-0 h-5 border-l-2 border-brand-light-purple/80"
                    ></div>
                    
                    <span class="truncate font-medium text-slate-800">
                        {{ category.category.fullname }}
                    </span>
                </div>

                <!-- COLUMN 2: Reassessment Toggle Switch -->
                <div class="w-52 shrink-0 flex items-center justify-start pr-2">
                    <Switch 
                        :label="mstrings.reassessment + '?'"
                        @change="reassess_change($event, category.category.id)"
                        :disabled="props.disablereassess || disabledByChildrenIds.includes(Number(category.category.id))"
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

            <!-- Recursive Execution Block -->
            <CategoryTree 
                v-if="props.depth < 3 && category.categories.length" 
                :nodes="category" 
                :depth="props.depth + 1"
                :engineering="props.engineering"
                :disablereassess="reassesscats.includes(Number(category.category.id)) || props.disablereassess"
                :parentid="Number(category.category.id)"
                @reassessup="reassessup"
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
        parentid: number;
    }

    const props = defineProps< IProps >();

    const emit = defineEmits<{
        reassessup: [targetParentId: number, enabled: boolean];
    }>();

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const engineeringcats = ref< number[] >([]);
    const reassesscats = ref< number[] >([]);
    
    // FIX: Track exactly WHICH row item IDs are currently locked by a child selection
    const disabledByChildrenIds = ref< number[] >([]);

    function eng_change(state: boolean | string, categoryid: number) {
        engineeringcats.value = engineeringcats.value.filter(id => id != categoryid);
        if (state === 'on' || state === true) {
            engineeringcats.value.push(Number(categoryid));
        }

        const flags = engineeringcats.value.map((id) => ({
            gradecategoryid: id,
            gradeitemid: 0,
            engexam: true,
            resit: false,
        }));
        
        moodleFetch('local_gugrades_write_flags', { flags }).catch(console.error);
    }

    function reassess_change(state: boolean | string, categoryid: number) {
        reassesscats.value = reassesscats.value.filter(id => id != categoryid);

        let emitstate = false;
        if (state === 'on' || state === true) {
            reassesscats.value.push(Number(categoryid));
            emitstate = true;
        }

        // Pass UP the current node's parent ID to tell the layer above who to disable
        emit('reassessup', props.parentid, emitstate);
    }

    /**
     * FIX: Handle the upward emit
     * @param targetId The ID of the category row at THIS level that needs updating
     * @param enabled Whether it should be disabled
     */
    function reassessup(targetId: number, enabled: boolean) {
        disabledByChildrenIds.value = disabledByChildrenIds.value.filter(id => id !== targetId);
        
        if (enabled) {
            disabledByChildrenIds.value.push(targetId);
        }

        // Continue bubbling up the tree. 
        // We tell our parent component instance to disable the row matching OUR parentid.
        emit('reassessup', props.parentid, enabled);
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
                engineeringcats.value = activeIds;
            }
        })
        .catch(console.error);
    });
</script>
