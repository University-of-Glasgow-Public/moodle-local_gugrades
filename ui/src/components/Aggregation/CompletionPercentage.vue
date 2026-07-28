<template>
    {{ user.completed }}&percnt;
</template>

<script setup lang="ts">
    interface IProps {
        user: Record<string, any>;
    }

    const props = defineProps< IProps >();
</script>