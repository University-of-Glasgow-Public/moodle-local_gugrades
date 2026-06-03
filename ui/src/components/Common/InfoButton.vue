<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <!-- info button -->
    <div v-if="!props.text" class="tooltip"  @click="info_clicked" :data-tip="mstrings['gradeiteminfo']">
        <button class="btn btn-outline mr-2 btn-sm" aria-label="Show grade item info">
            <Info :size="18"></Info>
        </button>
    </div>

    <!-- info link -->
    <a v-if="props.text" class="text-primary underline cursor-pointer" @click="info_clicked">{{ props.text }}</a>

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

    <!-- modal to show info-->
    <!--
    <VueModal v-model="showinfomodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="itemname">

        <div class="overflow-x-auto">
            <table class="table">
                <tbody>
                    <tr>
                        <th>{{ mstrings['name'] }}</th>
                        <td v-if="link == ''">{{ itemname }}</td>
                        <td v-else><a :href="link" target="_blank">{{ itemname }}</a></td>
                    </tr>
                    <tr>
                        <th>{{ mstrings['type'] }}</th>
                        <td>{{ itemtype }}</td>
                    </tr>
                    <tr>
                        <th>{{ mstrings['module'] }}</th>
                        <td>{{ itemmodule }}</td>
                    </tr>
                    <tr v-if="isscale">
                        <th>{{  mstrings['scale'] }}</th>
                        <td>{{ scalename }}</td>
                    </tr>
                    <tr v-if="!isscale && grademax">
                        <th>{{ mstrings['maxgrade'] }}</th>
                        <td>{{ grademax }}</td>
                    </tr>
                    <tr>
                        <th>{{ mstrings['weight'] }}</th>
                        <td>{{  weight }}</td>
                    </tr>
                    <tr v-if="categoryerror">
                        <td colspan="2" class="alert alert-warning">
                            {{ mstrings['categoryerror'] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-5">
            <TwButton color="warning" @click="showinfomodal = false">{{ mstrings['close'] }}</TwButton>
        </div>
    </VueModal>
-->
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwButton from '@/components/Tailwind/TwButton.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { Info, MessageCircleWarning } from '@lucide/vue';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';

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
