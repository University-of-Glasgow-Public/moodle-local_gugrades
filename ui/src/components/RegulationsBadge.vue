<template>
    <button class="btn btn-warning btn-soft text-sm text-error border-warning border-2">
        <Scale :size="16" />
        Regulations: <b>{{ regulation }}
        <span v-if="regulationextra" class="text-success uppercase">{{ regulationextra }}</span></b>
    </button>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import { Scale } from '@lucide/vue';

    const regulation = ref('');
    const regulationextra = ref('');

    onMounted(() => {
        moodleFetch(
            'local_gugrades_get_levelonecategories',
            {}
        )
        .then((result: any) => {

            regulation.value = result.regulation;
            regulationextra.value = result.regulationextra;
        })
        .catch((error) => {
            window.console.error(error);
        })
    });
</script>