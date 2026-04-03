<template>
    <TwAlert v-if="open" class="tw:my-2">
        <div class="tw:flex tw:justify-between tw:items-center tw:w-full">
            <span class="tw:flex-1" v-html="props.message"></span>
            <XCircleIcon class="tw:size-6 tw:text-black-500 tw:shrink-0 tw:tooltip" @click="open = false" data-tip="Dissmiss"></XCircleIcon>
        </div>
    </TwAlert>
</template>

<script setup lang="ts">
    import {computed, ref} from '@vue/runtime-core';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';
    import { XCircleIcon } from '@heroicons/vue/24/outline';

    const open = ref(true);

    const alertclass = computed(() => {
        return 'alert-' + props.alertstyle;
    })

    const props = defineProps({
        message: String,
        alertstyle: String,
    });
</script>