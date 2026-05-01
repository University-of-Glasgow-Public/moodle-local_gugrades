<template>
    <VueModal v-model="showmodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.confirm">
        <TwAlert color="error">
            <p><strong>{{  props.message }}</strong><br /><br />{{ mstrings.areyousure }}</p>
        </TwAlert>
        <div class="mt-2 pt-2">
            <TwButton color="primary" class="mr-1" @click="emit('confirm', true)">{{ mstrings.yes }}</TwButton>
            <TwButton color="warning" @click="emit('confirm', false)">{{ mstrings.no }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {toRef} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';

    const props = defineProps({
        show: Boolean,
        message: String,
    });

    const showmodal = toRef(props, 'show');

    const emit = defineEmits(['confirm']);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
</script>