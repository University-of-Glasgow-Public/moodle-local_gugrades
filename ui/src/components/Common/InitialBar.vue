<template>

    <div class="flex flex-wrap gap-1 mb-2">
        <div class="w-24">
            {{ label }}
        </div>
        <button
            v-for="letter in letters"
            :key="letter"
            class="px-1 border rounded font-mono"
            :class="{
                'bg-blue-600 text-white': selected?.toLowerCase() === letter.toLowerCase(),
                'hover:bg-gray-200': selected?.toLowerCase() !== letter.toLowerCase()
            }"
            @click="letterclicked(letter)"
            >
            {{ letter }}
        </button>
    </div>
</template>

<script setup lang="ts">
    import {ref, watch } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    const letters = ['ALL', ...'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')];

    const props = defineProps({
        'label': String,
        'selected': String
    });

    const emit = defineEmits(['selected']);

    const activeletter = ref('all');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    function letterclicked(letter: string) {
        if (letter) {
            activeletter.value = letter == 'ALL' ? 'all' : letter;
            emit('selected', activeletter.value);
        }
    }

    watch(() => props.selected, (selected) => {
        if (selected) {
            activeletter.value = selected;
            emit('selected', activeletter.value);
        }
    })
</script>