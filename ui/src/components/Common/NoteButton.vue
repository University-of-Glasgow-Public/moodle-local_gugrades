<template>
    <button class="btn btn-xs btn-dash" @click.prevent="opennote"><Plus :size="12" /> Note</button>

    <HeadlessModal :isopen="noteopen" @closed="noteopen = false">
        <template #title>
            Edit note
        </template>

        <TipTap v-model="note"></TipTap>

    </HeadlessModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Plus } from '@lucide/vue';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import TipTap from './TipTap.vue';

    const noteopen = ref(false);
    const note = ref('');

    function opennote() {
        console.log('open note');
        noteopen.value = true;
    }
</script>