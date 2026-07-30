<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a @click.prevent="alter_weights()" class="block px-3 py-2.5 text-sm cursor-pointer transition-colors hover:bg-university-blue/10">
        {{ mstrings.altertitle }}
    </a>

    <VueModal v-model="showaltermodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.altertitle">

        <UAlert v-if="loading">{{ mstrings.pleasewait }}</UAlert>

        <div v-if="!loading" class="scrollable-content">

            <!-- basic details of category -->
            <ul class="list-none">
                <li><b>{{ mstrings.category }}:</b> {{ categoryname }}</li>
                <li><b>{{ mstrings.username }}:</b> {{ userfullname }}</li>
                <li><b>{{ mstrings.idnumber }}:</b> {{ idnumber }}</li>
            </ul>

            <div class="divider"></div>

            <!-- grade items therein -->
            <table class="table mt-3 p-2">
                <thead>
                    <tr>
                        <th>{{ mstrings.gradeitem }}</th>
                        <th>{{ mstrings.gradetype }}</th>
                        <th>{{ mstrings.grade }}</th>
                        <th>{{ mstrings.defaultweights }}</th>
                        <th>{{ mstrings.alteredweights }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items" :key="item.gradeitemid">
                        <td><b>{{ item.fullname }}</b></td>
                        <td>{{ item.gradetype }}</td>
                        <td>{{ item.display }}</td>
                        <td>{{ item.originalweight }}</td>
                        <td>
                            <FormKit
                                type="number"
                                number="float"
                                outer-class="mb-3"
                                placeholder="new weight"
                                name="weight"
                                step="0.05"
                                validation="between:0,1"
                                validation-visibility="live"
                                v-model="item.alteredweight"
                            />
                        </td>
                    </tr>
                    <tr>
                        <td class="text-bold">{{ mstrings.sumofweights }}</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>{{ defaulttotal.toFixed(5) }}</td>
                        <td>{{ alteredtotal.toFixed(5) }}</td>
                    </tr>
                </tbody>
            </table>

            <UAlert v-if="!closeenough" variant="warning" class="my-2">{{ mstrings.donotaddto1 }}</UAlert>

            <div class="divider"></div>

            <!-- reason -->
            <div class="my-4">
                <FormKit
                    type="textarea"
                    outer-class="mb-3"
                    :label="mstrings.reasonforammendment"
                    name="reason"
                    v-model="reason"
                />
            </div>

            <div class="mt-2 flex gap-2">
                <UButton variant="primary" @click="save_altered_weights">{{ mstrings.save }}</UButton>
                <UButton vaiant="info" @click="revert_altered_weights">{{ mstrings.revert }}</UButton>
                <UButton variant="warning" @click="closemodal">{{ mstrings.cancel }}</UButton>
            </div>

        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, inject, computed} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import type { IAlterWeightItem, ISaveAlteredWeightItem } from '@/js/Interfaces';

    const showaltermodal = ref(false);
    const debug = ref({});
    const toast = useToast();
    const categoryname = ref('');
    const userfullname = ref('');
    const idnumber = ref('');
    const items = ref< IAlterWeightItem[] >([]);
    const reason = ref('');
    const loading = ref(true);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        userid: Number,
        itemid: Number,
        categoryid: Number,
        close: Function,
    });

    const emit = defineEmits([
        'weightsaltered'
    ]);

    /**
     * Close modal
     */
    function closemodal() {
        showaltermodal.value = false;
        if (props.close) {
            props.close();
        }
    }

    /**
     * Calculate altered weight total
     */
    const alteredtotal = computed(() => {
        var total = 0.0;
        items.value.forEach((item) => {
            total = total + item.alteredweight;
        });

        return total;
    });

    /**
     * Calculate default weight total
     */
     const defaulttotal = computed(() => {
        var total = 0.0;
        items.value.forEach((item) => {
            total = total + item.originalweight;
        });

        return total;
    });

    /**
     * Is total "close enough" to 1.0
     */
    const closeenough = computed(() => {
        var total = 0.0;
        items.value.forEach((item) => {
            total = total + item.alteredweight;
        });
        const error = Math.abs(total - 1);

        return error < 0.01;
    })

    /**
     * Alter weights button has been clicked
     */
    function alter_weights() {

        showaltermodal.value = true;

        reason.value = '';

        moodleFetch(
            'local_gugrades_get_alter_weight_form',
            {
                categoryid: props.categoryid,
                userid: props.userid,
            }
        )
        .then((result: any) => {
            categoryname.value = result.categoryname;
            userfullname.value = result.userfullname;
            idnumber.value = result.idnumber;
            items.value = result.items;
            loading.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Save altered weights
     */
    function save_altered_weights() {

        const saveitems: ISaveAlteredWeightItem[] = [];
        items.value.forEach((item) => {
            saveitems.push({
                gradeitemid: item.gradeitemid,
                weight: item.alteredweight,
            });
        });

        moodleFetch(
            'local_gugrades_save_altered_weights',
            {
                categoryid: props.categoryid,
                userid: props.userid,
                revert: false,
                reason: reason.value,
                items: saveitems,
            }
        )
        .then((result: any) => {
            emit('weightsaltered');
            toast.success(mstringstore.getMstring('weightsaltered'));

            closemodal();
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Revert altered weights
     */
     function revert_altered_weights() {

        moodleFetch(
            'local_gugrades_save_altered_weights',
            {
                categoryid: props.categoryid,
                userid: props.userid,
                revert: true,
                reason: '',
                items: [],
            }
        )
        .then(() => {
            emit('weightsaltered');
            toast.success(mstringstore.getMstring('weightsreverted'));

            closemodal();
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }
</script>

<style>
    .scrollable-modal {
    display: flex;
    flex-direction: column;
    height: calc(100% - 150px);
    }
    .scrollable-modal .vm-titlebar {
    flex-shrink: 0;
    }
    .scrollable-modal .vm-content {
    padding: 0;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    }
    .scrollable-modal .vm-content .scrollable-content {
    position: relative;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 15px 10px 15px;
    flex-grow: 1;
    }
    .scrollable-modal .scrollable-modal-footer {
    padding: 15px 0px 15px 0px;
    border-top: 1px solid #e5e5e5;
    margin-left: 0;
    margin-right: 0;
    }
</style>