<template>
    <vue-awesome-paginate
        :total-items="rowsCount"
        :items-per-page="rowsPerPage"
        :max-pages-shown="5"
        v-model="currentpage"
    ></vue-awesome-paginate>
</template>

<script setup lang="ts">
    import { ref, watch } from 'vue';

    interface PaginationProps {
        rowsPerPage: number;
        rowsCount: number;
        currentPage: number;
    }

    const props = defineProps<PaginationProps>();
    const currentpage = ref(1);

    const emits = defineEmits(['pagechange']);

    watch(currentpage, (newpage) => {
        emits('pagechange', newpage);
    });

</script>
