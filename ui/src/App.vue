<template>
    <div v-if="canview" id="mygrades_container" class="px-4 mb-8">
        <PageHeader />
        <TabMenu />
    </div>

    <div v-else class="p-8">
        You do not have permission to access MyGrades.
    </div>
</template>

<script setup lang="ts">
    import { onMounted, ref } from 'vue';
    import PageHeader from './components/PageHeader.vue';
    import TabMenu from '../src/views/TabMenu.vue';
    import { moodleFetch } from './js/moodlefetch.ts';

    const canview = ref(false);

    onMounted(() => {
        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:view'
            }
        )
        .then((result: any) => {
            canview.value = result.hascapability;
        })
        .catch((error) => {
            console.log(error);
        });
    })
</script>


