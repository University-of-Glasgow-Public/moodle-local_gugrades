import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useFilter = defineStore('filterstore', () => {
    const firstname = ref('all');
    const lastname = ref('all');

    return { firstname, lastname };
});