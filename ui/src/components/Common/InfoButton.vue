<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <!-- info button -->
    <div class="tw:ml-2 tw:tooltip"  @click="info_clicked" :data-tip="mstrings['gradeiteminfo']">
        <button class="tw:btn">
            <span v-if="props.text" class="tw:text-black-500"><u>{{ props.text }}</u></span>
            <InformationCircleIcon v-else class="tw:size-6 tw:text-black-500"></InformationCircleIcon>
        </button>
    </div>

    <!-- modal to show info-->
    <VueModal v-model="showinfomodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="itemname">

        <div class="tw:overflow-x-auto">
            <table class="tw:table">
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
                        <div class="alert alert-warning">
                            {{ mstrings['categoryerror'] }}
                        </div>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="tw:flex tw:justify-end tw:mt-5">
            <TwButton color="warning" @click="showinfomodal = false">{{ mstrings['close'] }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwButton from '@/components/Tailwind/TwButton.vue';
    import { InformationCircleIcon } from '@heroicons/vue/24/outline'
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

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