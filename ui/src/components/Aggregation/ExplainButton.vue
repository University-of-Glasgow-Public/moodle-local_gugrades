<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <a class="dropdown-item" href="#" @click.prevent="explain()">
        {{ mstrings.explain }}
    </a>

    <VueModal v-model="showexplainmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.explain">

        <TwAlert v-if="loading">{{ mstrings.pleasewait }}</TwAlert>

        <div v-if="!loading" class="scrollable-content">

            <!-- user stuffs -->
            <div class="flex justify-center items-center mb-8">
                <div class="flex items-center space-x-2">
                    <div class="avatar">
                        <div class="w-12 rounded-full">
                            <a :href="user!.profileurl" target="_profile">
                                <img :src="user!.pictureurl" :alt="user!.displayname" class="userpicture defaultuserpic" width="35" height="35"/>
                            </a>
                        </div>
                    </div>
                    <h1 class="text-lg font-bold">{{ user!.displayname }}</h1>
                </div>
            </div>

            <!-- details -->
            <div class="overflow-x-auto">
                <table class="table">
                    <tbody>
                        <tr>
                            <th>{{ mstrings.idnumber }}</th>
                            <td>{{ user!.idnumber }}</td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.completed }}</th>
                            <td>{{ user!.completed }}&percnt;</td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.gradecategory }}</th>
                            <td>{{ user!.itemname }}</td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.aggregatedgrade }}</th>
                            <td>{{ user!.displaygrade }}</td>
                        </tr>
                        <tr v-if="user!.rawgrade">
                            <th>{{ mstrings.rawgrade }}</th>
                            <td>{{ user!.rawgrade }}</td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.overridden }}</th>
                            <td><YesNo :yes="user!.overridden"></YesNo></td>
                        </tr>
                        <tr v-if="user!.showweights">
                            <th>{{ mstrings.alteredweights }}</th>
                            <td><YesNo :yes="user!.alteredweight"></YesNo></td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.strategy }}</th>
                            <td>{{ user!.strategy }}</td>
                        </tr>
                        <tr>
                            <th>{{ mstrings.gradetype }}</th>
                            <td>{{ user!.formattedatype }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- component grades -->
            <div>
                <h5 class="mt-8">{{ mstrings.grades }}</h5>
                <table class="table">
                    <tbody>
                        <tr v-for="field in user!.fields">
                            <th>{{ field.fullname }}</th>
                            <td>
                                <ul class="list-unstyled">
                                    <li><b>{{ field.display }}</b></li>
                                    <li v-if="!field.available">{{ mstrings.notavailable }}</li>
                                    <li v-if="field.dropped">{{ mstrings.dropped }}</li>
                                    <li v-if="field.hidden">{{ mstrings.hidden }}</li>
                                    <li v-if="field.overridden">{{ mstrings.overridden }}</li>
                                    <li v-if="user!.showweights">{{ mstrings.weight }}: {{ field.weight }}%</li>
                                    <li v-if="user!.showweights && field.normalisedweight">{{ mstrings.normalisedweight }}: {{ field.normalisedweight }}&percnt;</li>
                                    <li v-if="user!.showweights && user!.alteredweight">{{ mstrings.alteredweight }}: {{ field.alteredweight }}&percnt;</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- explanation -->
            <div>
                <h5 class="mt-8">{{ mstrings.explanation }}</h5>
                <div class="flex justify-center font-bold text-lg">{{ user!.explain }}</div>
            </div>

            <TwButton color="warning" @click="closemodal" class="mt-8">{{ mstrings.close }}</TwButton>
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