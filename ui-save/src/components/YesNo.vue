<template>
    <span v-if="props.yes">Yes</span>
    <span v-else>No</span>
</template>

<script setup lang="ts">
    const props = defineProps({
        yes: Boolean,
    });
</script>