<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a class="dropdown-item" href="#" @click.prevent="explain()">
        {{ mstrings.explain }}
    </a>

    <VueModal v-model="showexplainmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.explain">

        <template #titlebar>
            <div class="p-4 font-semibold flex justify-start gap-2 align-middle border-b border-base-300">
                <CircleAlert :size="14" class="mt-1"/>Grade aggregation explanation
            </div>
        </template>

        <TwAlert v-if="loading">{{ mstrings.pleasewait }}</TwAlert>

        <div v-if="!loading" class="scrollable-content">

            <!-- user stuffs -->
            <div class="flex justify-start gap-4 items-start mb-8 pb-2 border-b border-base-300">
                <div class="avatar">
                    <div class="ring-base-300 ring-offset-base-100 w-12 rounded-full ring-2 ring-offset-2">
                        <a :href="user!.profileurl" target="_profile">
                            <img :src="user!.pictureurl" :alt="user!.displayname" class="userpicture defaultuserpic" width="35" height="35"/>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="text-lg font-bold">{{ user!.displayname }}</div>
                    <div>{{ mstrings.idnumber }} {{ user!.idnumber }}</div>
                </div>
            </div>

            <!-- grade, completed, type -->
            <div class="flex gap-4 border-b border-base-300 pb-3">
                <div class="flex-1 text-left">
                    <div class="uppercase">{{ mstrings.aggregatedgrade }}</div>
                    <div class="text-info">{{ user!.displaygrade }}</div>
                </div>
                <div class="flex-1 pl-4 border-l border-base-300">
                    <div class="uppercase">{{ mstrings.completed }}</div>
                    <div class="font-bold">{{ user!.completed }}%</div>
                </div>
                <div class="flex-1 pl-4 border-l border-base-300">
                    <div class="uppercase">{{ mstrings.gradetype }}</div>
                    <div class="font-bold">{{ user!.formattedatype }}</div>
                </div>
            </div>

            <!-- configuration -->
            <div class="uppercase my-2">{{ mstrings.configuration }}</div>
            <div class="grid grid-cols-2 gap-4 mb-6 border-b border-base-300 pb-6 text-sm">
                <div class="flex justify-between items-end relative">
                    <div>{{ mstrings.strategy }}</div>
                    <div class="font-bold">{{ user!.strategy }}</div>
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-base-300 -mb-1"></div>
                </div>
                <div class="flex justify-between items-end relative">
                    <div>{{ mstrings.gradecategory }}</div>
                    <div class="font-bold">{{ user!.itemname }}</div>
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-base-300 -mb-1"></div>
                </div>
                <div class="flex justify-between items-end relative">
                    <div>{{ mstrings.overridden }}</div>
                    <div class="font-bold"><YesNo :yes="user!.overridden"></YesNo></div>
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-base-300 -mb-1"></div>
                </div>
                <div class="flex justify-between items-end relative">
                    <div>{{ mstrings.scale }}</div>
                    <div class="font-bold">{{ user!.formattedatype }}</div>
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-base-300 -mb-1"></div>
                </div>
            </div>

            <!-- Grades -->
            <div class="mb-6 border-b border-base-300 pb-6">
                <div class="uppercase my-2">{{ mstrings.grades }}</div>
                <div v-for="field in user!.fields" class="bg-warning/50 text-warning-content rounded-md shadow-md mb-2 p-3">
                    <div class="border-warning border-b pb-2">
                        <div class="flex justify-between pb-2">
                            <div class="font-semibold">{{ field.fullname }}</div>
                            <div class="font-semibold">{{ field.display }}</div>
                        </div>
                        <div class="flex justify-start space-x-1">
                            <div v-if="!field.available" class="badge badge-error">{{ mstrings.notavailable }}</div>
                            <div v-if="field.dropped" class="badge badge-error">{{ mstrings.dropped }}</div>
                            <div v-if="field.hidden" class="badge badge-error">{{ mstrings.hidden }}</div>
                            <div v-if="field.overridden" class="badge badge-error">{{ mstrings.overridden }}</div>
                        </div>
                    </div>
                    <div class="flex justify-start space-x-2 py-2">
                        <div v-if="user!.showweights">
                            <div class="text-sm">{{ mstrings.weight }}</div>
                            <div>{{ field.weight }}&percnt;</div>
                        </div>
                        <div v-if="user!.showweights && field.normalisedweight">
                            <div class="text-sm">{{mstrings.normalisedweight }}</div>
                            <div>{{ field.normalisedweight }}&percnt;</div>
                        </div>
                        <div v-if="user!.showweights && field.alteredweight">
                            <div class="text-sm">{{mstrings.alteredweight }}</div>
                            <div>{{ field.alteredweight }}&percnt;</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- explanation -->
            <div class="alert alert-warning bg-warning/50">
                {{ user!.explain }}
            </div>

            <div class="flex justify-end">
                <TwButton color="warning" @click="closemodal" class="mt-8">{{ mstrings.close }}</TwButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import YesNo from '@/components/YesNo.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import { CircleAlert } from '@lucide/vue';
    import type { IUser } from '@/js/Interfaces';

    const showexplainmodal = ref(false);
    const debug = ref({});
    const loading = ref(true);
    const user = ref< IUser >();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        userid: Number,
        itemid: Number,
        categoryid: Number,
        close: Function,
    });

    /**
     * Close modal
     */
    function closemodal() {
        showexplainmodal.value = false;
        if (props.close) {
            props.close();
        }
    }

    /**
     * Alter weights button has been clicked
     */
    function explain() {

        showexplainmodal.value = true;

        moodleFetch(
            'local_gugrades_get_explain_aggregation',
            {
                gradecategoryid: props.categoryid,
                userid: props.userid,
            }
        )
        .then((result: any) => {
            user.value = result;

            loading.value = false;
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