<template>
    <VueModal v-model="showmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.confirm">
        <UAlert variant="error">
            <p><strong>{{  props.message }}</strong><br /><br />{{ mstrings.areyousure }}</p>
        </UAlert>
        <div class="mt-2 pt-2 flex gap-2">
            <UButton variant="primary" @click="emit('confirm', true)">{{ mstrings.yes }}</UButton>
            <UButton variant="warning" @click="emit('confirm', false)">{{ mstrings.no }}</UButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {toRef} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UAlert from './UAlert.vue';
    import UButton from './UButton.vue';

    const props = defineProps({
        show: Boolean,
        message: String,
    });

    const showmodal = toRef(props, 'show');

    const emit = defineEmits(['confirm']);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
</script>