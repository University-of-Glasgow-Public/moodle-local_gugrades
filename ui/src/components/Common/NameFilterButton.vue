<template>
    <MenuButton @click="filteropen" :disabled="props.usershidden" :disabledReason="filterDisabledReason" iconName="Funnel">
        {{ mstrings.filterbyname }}
    </MenuButton>

    <HeadlessModal :isopen="showfiltermodal" @closed="filterclose">
        <template #title>
            {{ mstrings.filterbyname }}
        </template>
        <NameFilter @selected="selected"></NameFilter>
    </HeadlessModal>

</template>

<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import NameFilter from './NameFilter.vue';
    import MenuButton from '../Common/MenuButton.vue';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        usershidden: {
            type: Boolean,
            required: true,
        }
    });

    const filterDisabledReason = computed(() => {
        return props.usershidden ? mstringstore.getMstring('tooltipfilterhiddennames') : '';
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
