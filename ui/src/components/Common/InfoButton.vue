<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <!-- info button -->
    <UTooltip v-if="!props.text" @click="info_clicked" :text="mstrings['gradeiteminfo']">
        <button class="inline-flex items-center justify-center h-8 px-3 rounded-md bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-300 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed shadow-sm font-semibold text-xs gap-2 transition-all duration-150" aria-label="Show grade item info">
            <Info :size="18"></Info>
        </button>
    </UTooltip>

    <!-- info link -->
    <a v-if="props.text" class="text-white underline cursor-pointer" @click="info_clicked">{{ props.text }}</a>

    <HeadlessModal :isopen="showinfomodal" @closed="showinfomodal = false">
        <template #title>
            <div class="flex justify-start gap-2">
                <MessageCircleWarning />{{ itemname }}
            </div>
        </template>

        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col bg-warning/20 p-2 text-sm rounded">
                <span class="uppercase">{{ mstrings.type }}</span>
                <span class="font-bold">{{ itemtype }}</span>
            </div>
            <div class="flex flex-col bg-warning/20 p-2 text-sm rounded">
                <span class="uppercase">{{ mstrings.module }}</span>
                <span class="font-bold">{{ itemmodule }}</span>
            </div>
            <div v-if="isscale" class="flex flex-col bg-warning/20 p-2 text-sm rounded">
                <span class="uppercase">{{ mstrings.scale }}</span>
                <span class="font-bold">{{ scalename }}</span>
            </div>
            <div v-if="!isscale && grademax" class="flex flex-col bg-warning/20 p-2 text-sm rounded">
                <span class="uppercase">{{ mstrings.maxgrade }}</span>
                <span class="font-bold">{{ grademax }}</span>
            </div>
            <div class="flex flex-col bg-warning/20 p-2 text-sm rounded">
                <span class="uppercase">{{ mstrings.weight }}</span>
                <span class="font-bold">{{ weight }}&percnt;</span>
            </div>
            <div v-if="categoryerror" class="col-span-2 bg-warning/20 p-2 text-sm rounded">
                <span class="text-error">{{ mstrings.categoryerror }}</span>
            </div>
        </div>
    </HeadlessModal>

</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { Info, MessageCircleWarning } from '@lucide/vue';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import UTooltip from './UTooltip.vue';

    const showinfomodal = ref(false);
    const itemname = ref('');
    const itemtype = ref('');
    const itemmodule = ref('');
    const isscale = ref(false);
    const scalename = ref('');
    const grademax = ref(0);
    const weight = ref(0);
    const categoryerror = ref(false);
    const link = ref('');
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        itemid: Number,
        size: String,
        color: String,
        text: String,
    });

    const toast = useToast();

    /**
     * Info button clicked
     */
    function info_clicked() {

        moodleFetch(
            'local_gugrades_get_grade_item',
            {
                itemid: props.itemid,
            }
        )
        .then((result: any) => {
            itemname.value = result.itemname;
            itemtype.value = result.itemtype;
            itemmodule.value = result.itemmodule;
            isscale.value = result.isscale;
            scalename.value = result.scalename;
            grademax.value = result.grademax;
            weight.value = result.weight;
            categoryerror.value = result.categoryerror;
            link.value = result.link;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });

        showinfomodal.value = true;
    }

</script>
