<template>
    <span
        class="inline-flex items-center gap-1 rounded-full font-medium select-none whitespace-nowrap"
        :class="[sizeClasses, colorClasses]"
    >
        <slot />
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface iProps {
    variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral';
    size?: 'xs' | 'sm' | 'md' | 'lg';
    outline?: boolean;
}

const props = withDefaults(defineProps<iProps>(), {
    variant: 'neutral',
    size: 'sm',
    outline: false,
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs': return 'px-1.5 py-0.5 text-[10px]';
        case 'sm': return 'px-2 py-0.5 text-xs';
        case 'md': return 'px-2.5 py-1 text-sm';
        case 'lg': return 'px-3 py-1.5 text-base';
    }
});

// Solid (filled) variants
const solidColors: Record<string, string> = {
    primary: 'bg-university-blue text-white',
    secondary: 'bg-brand-dark-purple text-white',
    success: 'bg-brand-light-green text-university-blue',
    warning: 'bg-brand-light-yellow text-university-blue',
    danger: 'bg-brand-dark-red text-white',
    info: 'bg-brand-dark-blue text-white',
    neutral: 'bg-gray-100 text-gray-700',
};

// Outline variants — transparent bg, colored border + text
const outlineColors: Record<string, string> = {
    primary: 'bg-transparent border border-university-blue text-university-blue',
    secondary: 'bg-transparent border border-brand-dark-purple text-brand-dark-purple',
    success: 'bg-transparent border border-brand-dark-green text-brand-dark-green',
    warning: 'bg-transparent border border-brand-dark-red text-brand-dark-red', 
    danger: 'bg-transparent border border-brand-dark-red text-brand-dark-red',
    info: 'bg-transparent border border-brand-dark-blue text-brand-dark-blue',
    neutral: 'bg-transparent border border-gray-300 text-gray-600',
};

const colorClasses = computed(() => 
    props.outline ? outlineColors[props.variant] : solidColors[props.variant]
);
</script>