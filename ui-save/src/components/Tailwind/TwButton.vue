<template>
    <button v-if="disabled" :disabled="disabled" class="tw:btn tw:mr-1 tw:btn-disabled tw:btn-dash" tabindex="-1" role="button" aria-disabled="true">
        <slot></slot>
    </button>
    <button v-else class="tw:btn tw:mr-1" :class="btnclasses" v-bind="$attrs">
        <slot></slot>
    </button>
</template>

<script setup lang="ts">
    import { computed } from 'vue';

    const colorclasses = {
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
        },
        disabled: {
            type: Boolean,
            default: false,
        }
    });

    const btnclasses = computed(() => {
        let classes = [];
        if (props.color in colorclasses) {
            classes.push(colorclasses[props.color as keyof typeof colorclasses]);
        } else {
            classes.push(colorclasses['default']);
        }
        if (props.disabled) {
            classes.push('tw:btn-disabled');
        }
        return classes;
    });
</script>