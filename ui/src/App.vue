<template>
    <div v-if="canview && loaded" id="mygrades_container" class="px-4 mb-8">
        <PageHeader />
        <CategoryStartupSelect @finished="selectfinished = true" />
        <TabMenu v-if="selectfinished" />
        <PageFooter />
        <UserTour v-if="selectfinished" />

    </div>

    <div v-if="!canview && loaded" class="p-8">
        You do not have permission to access MyGrades.
    </div>

    <!-- Rendered outside #mygrades_container so the accessibility theme
         filters never apply to the launcher/panel itself. -->
    <AccessibilityPanel v-if="loaded" />
</template>

<script setup lang="ts">
    import { onMounted, ref } from 'vue';
    import PageHeader from './components/PageHeader.vue';
    import PageFooter from './components/PageFooter.vue';
    import TabMenu from '../src/views/TabMenu.vue';
    import { moodleFetch } from './js/moodlefetch.ts';
    import UserTour from './components/Common/UserTour.vue';
    import CategoryStartupSelect from './components/CategoryStartupSelect.vue';
    import AccessibilityPanel from './components/Common/AccessibilityPanel.vue';

    const canview = ref(false);
    const loaded = ref(false);
    const selectfinished = ref(false);

    onMounted(() => {
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:view'
            }
        )
        .then((result: any) => {
            canview.value = result.hascapability;
            loaded.value = true;

        })
        .catch((error) => {
            console.error(error);
        });
    })
</script>


