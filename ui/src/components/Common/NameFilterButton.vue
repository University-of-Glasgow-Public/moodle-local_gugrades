<template>
    <button @click="filteropen" :disabled="props.usershidden" class="btn btn-outline btn-secondary mr-2 btn-sm">
        <Funnel :size="18" />
        <span>{{ mstrings.filterbyname }}</span>
    </button>

    <HeadlessModal :isopen="showfiltermodal" @closed="filterclose">
        <template #title>
            {{ mstrings.filterbyname }}
        </template>
        <NameFilter @selected="selected"></NameFilter>
    </HeadlessModal>

</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Funnel } from '@lucide/vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import NameFilter from './NameFilter.vue';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        usershidden: {
            type: Boolean,
            required: true,
        }
    });

    const showfiltermodal = ref(false);
    const firstletter = ref('');
    const lastletter = ref('');

    function filteropen() {
        showfiltermodal.value = true;
    }

    function filterclose() {
        showfiltermodal.value = false;
    }

    /**
     * Handle letters selected
     */
    function selected(first: string, last: string) {
        firstletter.value = first;
        lastletter.value = last;
    }
</script>
