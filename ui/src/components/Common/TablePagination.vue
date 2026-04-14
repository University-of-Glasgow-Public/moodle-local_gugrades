<template>
    <vue-awesome-paginate
        :total-items="rowsCount"
        :items-per-page="rowsPerPage"
        :max-pages-shown="5"
        v-model="currentPage"
        @click="handle_click"
    ></vue-awesome-paginate>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';

    interface PaginationProps {
        rowsPerPage: number;
        rowsCount: number;
        startPage: number;
    }

    const props = defineProps<PaginationProps>();
    const currentPage = ref(1);

    const emits = defineEmits(['pagechange']);

    function handle_click(newpage: number) {
        //currentPage.value = newpage;
        emits('pagechange', newpage);
    }

    /**
     * BEWARE: EasyDataTable is configured to refresh when a page is changed,
     * so this is quite important (otherwise it resets to 1 each time)
     */
    onMounted(() => {
        currentPage.value = props.startPage;
    })

</script>
