<template>
    <div class="flex flex-col gap-1 mb-2">
        <!-- Title row -->
        <div class="font-semibold uppercase">
            {{ label }}
        </div>

        <!-- ALL button -->
        <div class="flex">
            <button
                class="px-2 btn border rounded font-mono"
                :class="{
                    'btn-primary': selected?.toLowerCase() === 'all',
                    'btn-outline btn-secondary': selected?.toLowerCase() !== 'all'
                }"
                @click="letterclicked('ALL')"
            >
                ALL
            </button>
        </div>

        <!-- Alphabet row 1: A–M -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="letter in lettersFirstHalf"
                :key="letter"
                class="px-1 btn border rounded font-mono"
                :class="{
                    'btn-primary': selected?.toLowerCase() === letter.toLowerCase(),
                    'btn-outline btn-secondary': selected?.toLowerCase() !== letter.toLowerCase()
                }"
                @click="letterclicked(letter)"
            >
                {{ letter }}
            </button>
        </div>

        <!-- Alphabet row 2: N–Z -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="letter in lettersSecondHalf"
                :key="letter"
                class="px-1 btn border rounded font-mono"
                :class="{
                    'btn-primary': selected?.toLowerCase() === letter.toLowerCase(),
                    'btn-outline btn-secondary': selected?.toLowerCase() !== letter.toLowerCase()
                }"
                @click="letterclicked(letter)"
            >
                {{ letter }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { ref, watch } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    const lettersFirstHalf  = alphabet.slice(0, 13);  // A–M
    const lettersSecondHalf = alphabet.slice(13);      // N–Z

    const props = defineProps({
        'label': String,
        'selected': String
    });

    const emit = defineEmits(['selected']);

    const activeletter = ref('all');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs(mstringstore);

    function letterclicked(letter: string) {
        if (letter) {
            activeletter.value = letter === 'ALL' ? 'all' : letter;
            emit('selected', activeletter.value);
        }
    }

    watch(() => props.selected, (selected) => {
        if (selected) {
            activeletter.value = selected;
            emit('selected', activeletter.value);
        }
    });
</script>