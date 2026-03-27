import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useMstrings = defineStore('mstringstore', () => {
    const mstrings = ref<Record<string, string>>({});

    // Add a getter function to safely access mstrings
    const getMstring = (key: string): string => {
        return mstrings.value[key] ?? "[[" + key + "]]";
    };

    return { mstrings, getMstring };
});