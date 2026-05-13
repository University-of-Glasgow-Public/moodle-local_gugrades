<template>
    <TwAlert v-if="open" class="my-2">
        <div class="flex justify-between items-center w-full">
            <span class="flex-1" v-html="props.message"></span>
            <SquareX class="shrink-0 tooltip" @click="open = false" data-tip="Dissmiss"></SquareX>
        </div>
    </TwAlert>
</template>

<script setup lang="ts">
    import {computed, ref} from 'vue';
    import TwAlert from '@/components/Tailwind/TwAlert.vue';
    import { SquareX } from '@lucide/vue';

    const open = ref(true);

    const alertclass = computed(() => {
        return 'alert-' + props.alertstyle;
    })

    const props = defineProps({
        message: String,
        alertstyle: String,
    });
</script>