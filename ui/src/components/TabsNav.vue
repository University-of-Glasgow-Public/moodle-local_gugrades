<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div class="mt-0 w-full" role="navigation">
        <TabGroup :defaultIndex="1">
            <TabList class="flex justify-start space-x-1 bg-base-100 p-1 border border-base-300 rounded-b-md w-full shadow-sm focus:outline-none">
                <Tab v-for="tab in tabs" v-slot="{ selected }" as="template">
                    <a
                        class="tab px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none flex items-center"
                        :class="{
                            'text-university-blue border-b-2 border-university-blue': selected,
                            'text-brand-dark-purple/80 hover:bg-brand-light-purple/10 hover:text-brand-dark-purple': !selected,
                        }"
                    >
                        <component :is="tab.icon" :size="16" class="mr-1" /> {{ tab.label }}
                    </a>
                </Tab>
            </TabList>
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
    import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
    import { MonitorCog, Camera, Calculator, Shield, Settings2, Table } from '@lucide/vue';

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
            label: mstrings.value.configure ?? 'Reassessment management',
            component: ConfigPage,
            icon: MonitorCog,
        },
        {
            label: mstrings.value.assessmentgradecapture ?? 'Assessment grade capture',
            component: CaptureTable,
            icon: Camera,
        },
        {
            label: mstrings.value.manageconversion ?? 'Manage conversion maps',
            component: ConversionPage,
            icon: Table
        },
        {
            label: mstrings.value.coursegradeaggregation ?? 'Course grade aggregation',
            component: AggregationTable,
            icon: Calculator
        },
        {
            label: mstrings.value.auditlog ?? 'Audit log',
            component: AuditPage,
            icon: Shield
        },
        {
            label: mstrings.value.settings ?? 'Settings',
            component: SettingsPage,
            icon: Settings2
        },
    ]);

</script>
