<template>
    <div class="alert alert-vertical alert-horizontal" :class="colorclass">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current h-6 w-6 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';

    const classes: Record<string, string> = {
        default: '',
        primary: 'alert-primary',
        secondary: 'alert-secondary',
        accent: 'alert-accent',
        info: 'alert-info',
        success: 'alert-success',
        warning: 'alert-warning',
        error: 'alert-error',
        danger: 'alert-error',
    };

    const props = defineProps({
        color: {
            type: String,
            default: 'primary'
        },
    });

    const colorclass = computed(() => {
        if (props.color in classes) {
            return classes[props.color];
        } else {
            return classes['default'];
        }
    });
</script>