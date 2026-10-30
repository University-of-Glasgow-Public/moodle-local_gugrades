<template>
    <img v-if="gradeitem.itemtype != 'manual'" class="w-6 h-6 inline-block object-contain" :title="gradeitem.info.itemmodule" :src="gradeitem.icon" />
    <SquarePen v-if="gradeitem.itemtype == 'manual'" :size="24" class="inline-block shrink-0 align-middle" role="img" :title="gradeitem.info.itemmodule" aria-label="Manual item" />
</template>

<script setup lang="ts">
    import { SquarePen } from '@lucide/vue';

    const props = defineProps({
        gradeitem: {
            type: Object,
            required: true
        }
    });
</script>