<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <UModal v-model="displaymodal" :showheaderclose="false"  title="Select initial top level category">
        <UAlert v-if="notsetup">{{ mstrings.notoplevel }}</UAlert>

        <template v-else>
            <UAlert class="mb-4">
                {{ mstrings.categoryselect }}
            </UAlert>

            <div class="flex flex-col gap-2" role="radiogroup" aria-label="Select initial top level category">
                <button
                    v-for="category in level1categories"
                    :key="category.id"
                    type="button"
                    role="radio"
                    @click="choose(category.id)"
                    class="cursor-pointer rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-900 text-left transition-colors hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-dark-blue/30 focus:outline-none"
                >
                    {{ category.fullname }}
                </button>
            </div>
        </template>
        <template #footer>&nbsp;</template>
    </UModal>
</template>

<script setup lang="ts">
    import { ref, onMounted, watch } from 'vue';
    import { storeToRefs } from 'pinia';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import type { ICategories } from '@/js/Interfaces';
    import { useLeve1Store } from '@/stores/level1';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from '@/components/Common/UAlert.vue';
    import UModal from './Common/UModal.vue';
    import { RadioGroup, RadioGroupOption } from '@headlessui/vue';

    const level1categories = ref< ICategories[] >([]);
    const notsetup = ref(false);
    const itemerror = ref(false);
    const debug = ref({});
    const displaymodal = ref(false);
    const level1store = useLeve1Store();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emits = defineEmits(['finished']);

    // Get the top level categories
    function getLevelOne() {
        moodleFetch(
            'local_gugrades_get_levelonecategories',
            {}
        )
        .then((result: any) => {

            level1categories.value = result.categories;
            notsetup.value = level1categories.value.length == 0;

            // if there's only one then might as well select it.
            if ((level1categories.value.length == 1) && (0 in level1categories.value) && !itemerror.value && !notsetup.value) {
                const onlycategoryid = level1categories.value[0].id;
                level1store.categoryid = onlycategoryid;
                displaymodal.value = false;
                emits('finished');
            } else {

                // Ok - if there's more than one we can display the modal now
                displaymodal.value = true;
            }
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        })
    }

    function choose(id: number) {
        if (id) {
            level1store.categoryid = id;
            displaymodal.value = false;
        }

        emits('finished');
    }

    onMounted(() => {
        itemerror.value = false;
        getLevelOne();
    });
</script>
