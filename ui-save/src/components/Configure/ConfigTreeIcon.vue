<template>
    <img v-if="gradeitem.itemtype != 'manual'" class="icon itemicon" :title="gradeitem.info.itemmodule" :src="gradeitem.icon" />
    <i v-if="gradeitem.itemtype == 'manual'" class="icon fa fa-pen-to-square fa-fw icon itemicon" role="img" :title="gradeitem.info.itemmodule" aria-label="Manual item"></i>
</template>

<script setup lang="ts">
    const props = defineProps({
        gradeitem: {
            type: Object,
            required: true
        }
    });
</script>