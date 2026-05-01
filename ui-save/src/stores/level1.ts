import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { ICategories } from '@/js/Interfaces';

export const useLeve1Store = defineStore('level1', () => {

    // Store the currently selected level 1 categoryid
    const categoryid = ref(0);

    // Get level1 category ensuring in valid IDs.
    function getvalidcategoryid(validcats: ICategories[]) {

        // Extract id fields
        const ids = validcats.map((cat) => cat.id);

        if (ids.includes(categoryid.value)) {
            return categoryid.value;
        } else {
            return 0;
        }
    }

    return { categoryid, getvalidcategoryid };
})