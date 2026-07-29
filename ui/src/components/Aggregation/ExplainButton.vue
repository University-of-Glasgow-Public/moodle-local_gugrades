<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a class="dropdown-item" href="#" @click.prevent="explain()">
        {{ mstrings.explain }}
    </a>

    <VueModal v-model="showexplainmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.explain">

        <template #titlebar>
            <div class="p-4 font-semibold flex justify-between gap-2 align-middle border-b border-base-300">
                <div class="flex justify-start gap-2">
                    <CircleAlert :size="14" class="mt-1"/>
                    <span>Grade aggregation explanation</span>
                </div>
                <div>
                    <UButton appearance="outline" size="sm" @click="closemodal"><X :size="18"/></UButton>
                </div>
            </div>
        </template>

        <UAlert v-if="loading">{{ mstrings.pleasewait }}</UAlert>

        <div v-if="!loading" class="scrollable-content">

            <!-- user stuffs -->
            <div class="flex items-center gap-5 pb-5 mb-8 border-b border-brand-light-purple/30">
                <!-- User Avatar Section -->
                <div class="relative shrink-0">
                    <a :href="user!.profileurl" 
                    target="_profile" 
                    class="block w-14 h-14 rounded-full ring-2 ring-university-blue ring-offset-2 ring-offset-white overflow-hidden transition-transform duration-200 hover:scale-105">
                        <img :src="user!.pictureurl" 
                            :alt="user!.displayname" 
                            class="w-full h-full object-cover" 
                            width="56" 
                            height="56"/>
                    </a>
                </div>

                <!-- User Information Section -->
                <div class="flex flex-col gap-1 min-w-0">
                    <h2 class="text-xl font-bold tracking-tight text-brand-dark-purple truncate">
                        {{ user!.displayname }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600 font-sans">
                        <span class="font-medium">
                            {{ mstrings.idnumber }}: <span class="font-mono text-slate-800">{{ user!.idnumber }}</span>
                        </span>
                        <span class="hidden sm:inline text-slate-300">•</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-light-blue text-brand-dark-blue">
                            {{ user!.ugpg }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- grade, completed, type -->
            <div class="flex gap-4 border-b border-brand-light-purple/30 pb-3 text-slate-600">
                <div class="flex-1 text-left">
                    <div class="uppercase text-xs font-semibold tracking-wider text-brand-dark-purple/70 mb-1">{{ mstrings.aggregatedgrade }}</div>
                    <!-- Replaced text-info with your custom brand accent -->
                    <div class="text-university-blue font-semibold"><GradeColor :grade="user!.displaygrade"/></div>
                </div>
                <div v-if="user && user.regulationname == 'original'" class="flex-1 pl-4 border-l border-brand-light-purple/30">
                    <div class="uppercase text-xs font-semibold tracking-wider text-brand-dark-purple/70 mb-1">{{ mstrings.completed }}</div>
                    <div class="font-bold text-slate-900">{{ user!.completed }}%</div>
                </div>
                <div class="flex-1 pl-4 border-l border-brand-light-purple/30">
                    <div class="uppercase text-xs font-semibold tracking-wider text-brand-dark-purple/70 mb-1">{{ mstrings.gradetype }}</div>
                    <div class="font-bold text-slate-900">{{ user!.formattedatype }}</div>
                </div>
            </div>

            <!-- configuration -->
            <div class="uppercase text-xs font-bold tracking-wider text-brand-dark-purple mt-4 mb-2">{{ mstrings.configuration }}</div>
            <div class="grid grid-cols-2 gap-4 border-b border-brand-light-purple/30 pb-4 text-sm text-slate-600">
                <div>
                    <div class="text-xs text-slate-500 mb-0.5">{{ mstrings.strategy }}</div>
                    <div class="font-bold text-slate-900">{{ user!.strategy }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 mb-0.5">{{ mstrings.gradecategory }}</div>
                    <div class="font-bold text-slate-900">{{ user!.itemname }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 mb-0.5">{{ mstrings.overridden }}</div>
                    <div class="font-bold text-slate-900"><YesNo :yes="user!.overridden"></YesNo></div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 mb-0.5">{{ mstrings.scale }}</div>
                    <div class="font-bold text-slate-900">{{ user!.formattedatype }}</div>
                </div>
            </div>

            <!-- Grades -->
            <div class="mb-6 border-b border-brand-light-purple/30 pb-6">
                <div class="uppercase text-xs font-bold tracking-wider text-brand-dark-purple mt-4 mb-3">{{ mstrings.grades }}</div>
                
                <!-- Replaced bg-warning/20 and text-warning-content with your light/dark yellow theme -->
                <div v-for="field in user!.fields" class="bg-brand-light-yellow/50 text-slate-800 rounded-lg border border-brand-light-yellow/50 shadow-sm mb-3 px-4 py-2.5">
                    <div class="border-brand-light-yellow/40 border-b pb-2">
                        <div class="flex justify-between items-center pb-1.5">
                            <div class="font-semibold text-brand-dark-purple">{{ field.fullname }}</div>
                            <div class="font-bold"><GradeColor :grade="field.display" size="text-lg"/></div>
                        </div>
                        
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            <div v-if="!field.available" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-brand-light-red text-brand-dark-red">{{ mstrings.notavailable }}</div>
                            <div v-if="field.dropped" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-brand-light-red text-brand-dark-red">{{ mstrings.dropped }}</div>
                            <div v-if="field.hidden" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-brand-light-red text-brand-dark-red">{{ mstrings.hidden }}</div>
                            <div v-if="field.overridden" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-brand-light-red text-brand-dark-red">{{ mstrings.overridden }}</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-x-6 gap-y-1 pt-2 text-sm text-slate-600">
                        <div v-if="user!.showweights" class="flex gap-1.5">
                            <span class="text-slate-500">{{ mstrings.weight }}:</span>
                            <span class="font-semibold text-slate-800">{{ field.weight }}&percnt;</span>
                        </div>
                        <div v-if="user!.showweights && field.normalisedweight" class="flex gap-1.5">
                            <span class="text-slate-500">{{mstrings.normalisedweight }}:</span>
                            <span class="font-semibold text-slate-800">{{ field.normalisedweight }}&percnt;</span>
                        </div>
                        <div v-if="user!.showweights && field.alteredweight" class="flex gap-1.5">
                            <span class="text-slate-500">{{mstrings.alteredweight }}:</span>
                            <span class="font-semibold text-slate-800">{{ field.alteredweight }}&percnt;</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- explanation -->
            <!-- Assuming UAlert is a Nuxt UI / custom component, overridden variant/class attributes to use your warning colors -->
            <UAlert 
                :ui="{ wrapper: 'bg-brand-light-yellow border-l-4 border-brand-light-yellow text-slate-800 p-4 rounded-r-lg shadow-sm' }"
                title="Explanation"
            >
                {{ user!.explain }}
            </UAlert>

            <div class="flex justify-end">
                <!-- Updated the custom button class styles to use your primary university-blue color instead of warning -->
                <button 
                    type="button"
                    @click="closemodal" 
                    class="mt-8 px-5 py-2 rounded-lg font-semibold text-sm text-white bg-university-blue hover:bg-university-blue/90 shadow transition-colors focus:outline-none focus:ring-2 focus:ring-university-blue focus:ring-offset-2"
                >
                    {{ mstrings.close }}
                </button>
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
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import { CircleAlert, X } from '@lucide/vue';
    import type { IUser } from '@/js/Interfaces';
    import GradeColor from '../Common/GradeColor.vue';

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
            console.log(user.value);

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