import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useActivityTreeStore = defineStore('activitytree', () => {

    // Array of trees for each top level category.
    const trees = ref< string[] >([]);

    // Array of errors.
    const errors = ref< string[] >([]);

    // Flag to declare that trees have been configured
    const ready = ref(false);

    return { trees, errors, ready };
})