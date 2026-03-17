<template>
    <div class="tw:alert tw:alert-soft tw:alert-vertical tw:sm:alert-horizontal" :class="colorclass">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="tw:stroke-current tw:h-6 tw:w-6 tw:shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <slot></slot>
    </div>
</template>

<script setup>
    import { computed } from 'vue';

    const classes = {
        default: '',
        primary: 'tw:alert-primary',
        secondary: 'tw:alert-secondary',
        accent: 'tw:alert-accent',
        info: 'tw:alert-info',
        success: 'tw:alert-success',
        warning: 'tw:alert-warning',
        error: 'tw:alert-error',
        danger: 'tw:alert-error',
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