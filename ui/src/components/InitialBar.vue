<template>
    <div class="initialbar d-flex flex-wrap justify-content-center justify-content-md-start">
        <span class="initialbarlabel mr-2">{{ props.label }}</span>

        <nav class="initialbargroups d-flex flex-wrap justify-content-center justify-content-md-start">
            <ul class="pagination pagination-sm">
                <li class="initialbarall page-item" :class="{active: is_active('all')}">
                    <a data-initial="" class="page-link" href="#" @click="letterclicked('all')">{{ mstrings.all }}</a>
                </li>
            </ul>
            <ul class="pagination pagination-sm">
                <li v-for="letter in letters1" :key="letter" class="page-item" :class="{active: is_active(letter)}">
                    <a class="page-link" href="#" @click.prevent="letterclicked(letter)">{{letter}}</a>
                </li>
            </ul>
            <ul class="pagination pagination-sm">
                <li v-for="letter in letters2" :key="letter" class="page-item" :class="{active: is_active(letter)}">
                    <a class="page-link" href="#" @click.prevent="letterclicked(letter)">{{letter}}</a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup lang="ts">
    import {ref, computed, watch } from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    const props = defineProps({
        'label': String,
        'selected': String
    });

    const emit = defineEmits(['selected']);

    const activeletter = ref('all');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const letters1 = computed(() => {
        return Array.from("ABCDEFGHIJKLM");
    });

    const letters2 = computed(() => {
        return Array.from("NOPQRSTUVWXYZ");
    });

    function letterclicked(letter: string) {
        activeletter.value = letter;
        emit('selected', letter);
    }

    function is_active(letter: string) {
        return activeletter.value == letter;
    }

    watch(() => props.selected, (selected) => {
        if (selected) {
            activeletter.value = selected;
            emit('selected', activeletter.value);
        }
    })
</script>