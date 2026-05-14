<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <button @click="open_modal" class="btn btn-outline btn-secondary mr-2 btn-sm">
        <Save :size="18" />
        {{ mstrings.exportaggregation }}
    </button>

    <VueModal v-model="showexportmodal" :enableClose="false" modalClass="rounded max-w-3xl overflow-y-auto" :title="mstrings.exportaggregation">

        <PleaseWait v-if="pleasewait"></PleaseWait>

        <!-- step to select plugin and filename -->
        <div v-if="step == 'selectplugin'" class="mb-5">
            <FormKit
                type="form"
                @submit="plugin_selected()"
                :submit-label="mstrings.next"
            >
                <FormKit
                    type="select"
                    :label="mstrings.selectexport"
                    :options="plugins"
                    v-model="selectedplugin"
                ></FormKit>

                <FormKit
                    class="mt-2"
                    type="text"
                    :label="mstrings.exportfilename"
                    validation="required"
                    validation-visibility="live"
                    v-model="filename"
                ></FormKit>
            </FormKit>
        </div>

        <!-- step to select form fields-->
        <div v-if="(step == 'selectfields') && hasform" class="mb-5 scrollable-content">
            <FormKit
                type="form"
                @submit="fields_selected()"
                :submit-label="mstrings.export"
            >

                <TwAlert class="mb-5">{{ mstrings.selectfields }}</TwAlert>

                <div class="mb-2">
                    <TwButton color="info" class="mr-1" @click="all_selected">{{ mstrings.checkall }}</TwButton>
                    <TwButton color="secondary"  @click="none_selected">{{ mstrings.checknone }}</TwButton>
                </div>

                <FormKit
                    v-for="field in form"
                    type="checkbox"
                    :label="field.description"
                    :label-class="field.category ? 'font-weight-bold' : ''"
                    v-model="selected[field.identifier]"
                />
            </FormKit>
        </div>

        <!-- alternatively -->
        <div v-if="(step == 'selectfields') && !hasform" class="mb-5 scrollable-content">
            <TwAlert>{{ mstrings.noselectfields }}</TwAlert>
            <TwButton color="primary" class="mt-2" @click="fields_selected()">{{  mstrings.next }}</TwButton>"
        </div>

        <div class="flex justify-end">
            <TwButton color="warning" @click="close_modal()">{{ mstrings.cancel }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import { useToast } from "vue-toastification";
    import { saveAs } from 'file-saver';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import type { IAggregationExportPlugin, IMenuItem, IAggregationExportForm } from '@/js/Interfaces';
    import { Save } from '@lucide/vue';

    const showexportmodal = ref(false);
    const allnone = ref(false);
    const pleasewait = ref(false);
    const plugins = ref< IMenuItem[] >([]);
    const selectedplugin = ref('custom');
    const debug = ref({});
    const step = ref('selectplugin');
    const hasform = ref(false);
    const form = ref< IAggregationExportForm[] >([]);
    const selected = ref<Record<string, boolean>>({});
    const filename = ref('');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toast = useToast();

    const props = defineProps({
        categoryid: Number,
        groupid: Number,
        itemname: String,
    });

    /**
     * Load initial plugin options
     */
    function open_modal() {

        pleasewait.value = true;
        step.value = 'selectplugin';

        moodleFetch(
            'local_gugrades_get_aggregation_export_plugins',
            {
                gradecategoryid: props.categoryid,
            }
        )
        .then((result: any) => {
            const options: IAggregationExportPlugin[] = result.plugins;
            plugins.value = [];
            options.forEach(option => {
                plugins.value.push({
                    label: option.description,
                    value: option.name,
                });
            });
            filename.value = result.filename;
            pleasewait.value = false;
        })
        .catch((error) => {
            showexportmodal.value = false;
            debug.value = error;
        });

        showexportmodal.value = true;
    }

    /**
     * Initialise selected array
     */
    function initialise_selected() {
        form.value.forEach(field => {
            selected.value[field.identifier] = field.selected;
        });
    }

    /**
     * Make all fields selected
     */
    function all_selected() {
        form.value.forEach(field => {
            selected.value[field.identifier] = true;
        });
    }

    /**
     * Make all fields unselected
     */
     function none_selected() {
        form.value.forEach(field => {
            selected.value[field.identifier] = false;
        });
    }

    /**
     * Plugin type has been selected
     * Get the settings form for selected (if there is one)
     */
    function plugin_selected() {
        pleasewait.value = true;

        moodleFetch(
            'local_gugrades_get_aggregation_export_form',
            {
                gradecategoryid: props.categoryid,
                plugin: selectedplugin.value,
            }
        )
        .then((result:any) => {
            hasform.value = result.hasform;
            form.value = result.form;
            if (hasform.value) {
                initialise_selected();
            }
            pleasewait.value = false;
            step.value = "selectfields";
        })
        .catch((error) => {
            showexportmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Fields required have been selected on form
     * (If the plugin has a form)
     *
     */
    function fields_selected() {
        pleasewait.value = true;

        // Munge selected array into required form.
        const paramform = [];
        for (const [identifier, isselect] of Object.entries(selected.value)) {
            paramform.push({
                identifier: identifier,
                selected: isselect,
            });
        }

        moodleFetch(
            'local_gugrades_get_aggregation_export_data',
            {
                gradecategoryid: props.categoryid,
                plugin: selectedplugin.value,
                groupid: props.groupid,
                form: paramform,
            }
        )
        .then((result: any) => {
            const csv = result['csv'];
            const d = new Date();
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
            saveAs(blob, filename.value + '.csv');

            showexportmodal.value = false;
        })
        .catch((error) => {
            showexportmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Close the modal
     */
    function close_modal() {
        showexportmodal.value = false;
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
