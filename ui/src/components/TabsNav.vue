<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="mt-5" role="navigation">
        <TabGroup :defaultIndex="1">
            <div class="flex justify-between">
                <TabList class="flex justify-start space-x-1 bg-base-200 p-1 rounded-box w-fit shadow-sm">
                    <Tab v-for="tab in tabs" v-slot="{ selected }" as="template">
                        <a
                            class="tab px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                            :class="{
                                'bg-primary text-primary-content shadow-md': selected,
                                'text-base-content/80 hover:bg-base-100 hover:text-base-content': !selected,
                            }"
                        >
                            {{ tab.label }}
                        </a>
                    </Tab>
                </TabList>
                <ThemeSelect></ThemeSelect>
            </div>
            <TabPanels >
                <TabPanel v-for="tab in tabs" role="main">
                    <component :is="tab.component" />
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import ThemeSelect from './Common/ThemeSelect.vue';
    import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';

    import ConfigPage from '@/views/ConfigPage.vue';
    import CaptureTable from '@/views/CaptureTable.vue';
    import AggregationTable from '@/views/AggregationTable.vue';
    import ConversionPage from '@/views/ConversionPage.vue';
    import SettingsPage from '@/views/SettingsPage.vue';
    import AuditPage from '@/views/AuditPage.vue';

    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const tabs = ref([
        {
            label: mstrings.value.configure ?? '',
            component: ConfigPage
        },
        {
            label: mstrings.value.assessmentgradecapture ?? '',
            component: CaptureTable
        },
        {
            label: mstrings.value.manageconversion ?? '',
            component: ConversionPage
        },
        {
            label: mstrings.value.coursegradeaggregation ?? '',
            component: AggregationTable
        },
        {
            label: mstrings.value.auditlog ?? '',
            component: AuditPage
        },
        {
            label: mstrings.value.settings ?? '',
            component: SettingsPage
        },
    ]);

</script>
