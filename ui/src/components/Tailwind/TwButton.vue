<template>
    <button class="tw:btn tw:mr-1" :class="colorclass" v-bind="$attrs">
        <slot></slot>
    </button>
</template>

<script setup>
    import { computed } from 'vue';

    const classes = {
        default: '',
        primary: 'tw:btn-primary',
        secondary: 'tw:btn-secondary',
        accent: 'tw:btn-accent',
        info: 'tw:btn-info',
        success: 'tw:btn-success',
        warning: 'tw:btn-warning',
        error: 'tw:btn-error',
    };

    const props = defineProps({
        color: {
            type: String,
            default: 'primary'
        }
    });

    const colorclass = computed(() => {
        if (props.color in classes) {
            return classes[props.color];
        } else {
            return classes['default'];
        }
    });
</script>