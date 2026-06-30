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
                        :active="reassesscats.includes(Number(category.category.id))"
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
    const disabledByChildrenIds = ref< number[] >([]);

    /**
     * Engineering Change
     */
    function eng_change(state: boolean | string, categoryid: number) {
        const isEnabled = state === 'on' || state === true;

        moodleFetch('local_gugrades_read_flags', {})
        .then((result: any) => {
            let currentFlags: any[] = result?.flags || [];
            currentFlags = currentFlags.filter(f => !(Number(f.gradecategoryid) === Number(categoryid) && f.engexam));

            if (isEnabled) {
                currentFlags.push({
                    gradecategoryid: categoryid,
                    gradeitemid: 0,
                    engexam: true,
                    resit: false
                });
            }

            engineeringcats.value = currentFlags.filter(f => f.engexam).map(f => Number(f.gradecategoryid));
            return moodleFetch('local_gugrades_write_flags', { flags: currentFlags as IFlag[] });
        })
        .catch(console.error);
    }

    /**
     * Reassessment Change
     */
    function reassess_change(state: boolean | string, categoryid: number) {
        const isEnabled = state === 'on' || state === true;

        reassesscats.value = reassesscats.value.filter(id => id != categoryid);
        if (isEnabled) {
            reassesscats.value.push(Number(categoryid));
        }

        moodleFetch('local_gugrades_read_flags', {})
        .then((result: any) => {
            let currentFlags: any[] = result?.flags || [];
            currentFlags = currentFlags.filter(f => !(Number(f.gradecategoryid) === Number(categoryid) && f.resit));

            if (isEnabled) {
                currentFlags.push({
                    gradecategoryid: categoryid,
                    gradeitemid: 0,
                    engexam: false,
                    resit: true
                });
            }

            return moodleFetch('local_gugrades_write_flags', { flags: currentFlags as IFlag[] });
        })
        .catch(console.error);

        emit('reassessup', props.parentid, isEnabled);
    }

    /**
     * Handle runtime updates bubbling from recursive branches
     */
    function reassessup(targetId: number, enabled: boolean) {
        disabledByChildrenIds.value = disabledByChildrenIds.value.filter(id => id !== targetId);
        
        if (enabled) {
            disabledByChildrenIds.value.push(targetId);
        }

        emit('reassessup', props.parentid, enabled);
    }

    const hasNoCategories = computed(() => {
        return !props.nodes.categories || props.nodes.categories.length === 0;
    });

    /**
     * Parse DB entries and explicitly establish active/disabled states across all nested lines
     */
    onMounted(() => {
        moodleFetch('local_gugrades_read_flags', {})
        .then((result: any) => {
            if (result && result.flags) {
                const rawFlags: IFlag[] = result.flags;

                // 1. Map Engineering flags strictly casting IDs to pure integers
                engineeringcats.value = rawFlags
                    .filter((flag: IFlag) => flag.gradecategoryid && flag.engexam)
                    .map((flag: IFlag) => Number(flag.gradecategoryid));

                // 2. Isolate all active reassessment IDs from the database payload
                const allActiveResitIds = rawFlags
                    .filter((flag: IFlag) => flag.gradecategoryid && flag.resit)
                    .map((flag: IFlag) => Number(flag.gradecategoryid));

                // FIX 1: Match and check nodes correctly by running a pure array scan against our sub-items
                if (props.nodes && props.nodes.categories) {
                    
                    // Filter matching ids explicitly casting to Number to dodge type coercion bugs
                    reassesscats.value = props.nodes.categories
                        .map(c => Number(c.category.id))
                        .filter(id => allActiveResitIds.includes(id));

                    // 3. FIX 2: Evaluate child subtrees and handle locking up the hierarchy chains
                    props.nodes.categories.forEach(cat => {
                        const id = Number(cat.category.id);
                        
                        if (hasActiveChildResit(cat, allActiveResitIds)) {
                            // Lock this row if a child under it is selected
                            disabledByChildrenIds.value.push(id);
                            
                            // Bubble the active state up the UI component tree on mount
                            emit('reassessup', props.parentid, true);
                        }
                    });
                }
            }
        })
        .catch(console.error);
    });

    /**
     * Deep tree recursion matcher utility
     */
    function hasActiveChildResit(node: any, activeIds: number[]): boolean {
        if (!node.categories || node.categories.length === 0) return false;
        
        return node.categories.some((child: any) => {
            const childId = Number(child.category.id);
            if (activeIds.includes(childId)) {
                return true;
            }
            return hasActiveChildResit(child, activeIds);
        });
    }
</script>
