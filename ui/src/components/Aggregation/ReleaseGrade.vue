<template>
    <GradeColor :grade="grade" />
</template>

<script setup lang="ts">
    import GradeColor from '../Common/GradeColor.vue';

    interface IProps {
        grade: string;
    }

    const props = defineProps< IProps >();
</script>