<template>
    <button v-if="disabled" :disabled="disabled" class="btn mr-1 btn-disabled btn-dash" tabindex="-1" role="button" aria-disabled="true">
        <slot></slot>
    </button>
    <button v-else class="btn mr-1" :class="btnclasses" v-bind="$attrs">
        <slot></slot>
    </button>
</template>

<script setup lang="ts">
    import { computed } from 'vue';

    const colorclasses = {
        default: '',
        primary: 'btn-primary',
        secondary: 'btn-secondary',
        accent: 'btn-accent',
        info: 'btn-info',
        success: 'btn-success',
        warning: 'btn-warning',
        error: 'btn-error',
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
            classes.push('btn-disabled');
        }
        return classes;
    });
</script>