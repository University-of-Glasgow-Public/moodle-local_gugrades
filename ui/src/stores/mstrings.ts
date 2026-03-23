import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useMstrings = defineStore('mstringstore', () => {
    const mstrings = ref([]);

    return { mstrings };
});