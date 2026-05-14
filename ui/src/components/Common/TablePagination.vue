<template>
    <vue-awesome-paginate
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :max-pages-shown="5"
        v-model="currentPage"
        @click="handle_click"
    ></vue-awesome-paginate>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';

    interface PaginationProps {
        totalItems: number;
        itemsPerPage: number;
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

<style>
  .pagination-container {
    display: flex;

    column-gap: 10px;
  }

  .paginate-buttons {
    height: 40px;

    width: 40px;

    border-radius: 20px;

    cursor: pointer;

    background-color: rgb(242, 242, 242);

    border: 1px solid rgb(217, 217, 217);

    color: black;
  }

  .paginate-buttons:hover {
    background-color: #d8d8d8;
  }

  .active-page {
    background-color: #3498db;

    border: 1px solid #3498db;

    color: white;
  }

  .active-page:hover {
    background-color: #2988c8;
  }
</style>
