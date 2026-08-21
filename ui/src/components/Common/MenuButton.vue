<!-- MenuButton.vue -->
<template>
  <UTooltip
    class="inline-flex rounded-md focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-dark-blue"
    :tabindex="disabledTooltip ? 0 : undefined"
    :text="disabledTooltip"
    position="above"
  >
    <component
      :is="props.href ? 'a' : 'button'"
      :href="props.href || undefined"
      :type="props.href ? undefined : 'button'"
      :disabled="props.href ? undefined : disabled"
      :aria-disabled="disabled || undefined"
      :aria-label="disabledTooltip || undefined"
      @click="emit('click', $event)"
      :class="[
        'inline-flex items-center justify-center h-8 px-3 rounded-md bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-300 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed disabled:pointer-events-none shadow-sm font-semibold text-xs gap-2 transition-all duration-150 no-underline',
        props.wide ? 'w-full min-w-36' : 'w-36'
      ]"
    >
      <!-- Dynamic Component injector rendering the type-safe Lucide component -->
      <component 
        :is="resolvedIcon" 
        :size="16" 
        class="shrink-0 opacity-70" 
        :class="props.warning ? '!text-brand-dark-pink' : ''"
      />
      
      <span :class="props.wide ? 'whitespace-nowrap' : 'truncate'">
        <slot></slot>
      </span>
    </component>
  </UTooltip>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import * as icons from '@lucide/vue';
    import type { Component } from 'vue';
    import UTooltip from './UTooltip.vue';

    // 1. Extract all valid icon export names from Lucide to prevent string typos
    type LucideIconName = keyof typeof icons;

    // 2. Define strict TypeScript Props interface
    interface Props {
      iconName: LucideIconName;
      disabled?: boolean;
      disabledReason?: string;
      warning?: boolean;
      wide?: boolean;
      href?: string;
    }

    const props = withDefaults(defineProps<Props>(), {
      disabled: false,
      disabledReason: '',
      wide: false,
    });

    // 3. Define type-safe Emits
    const emit = defineEmits<{
    (e: 'click', event: MouseEvent): void
    }>();

    // Native disabled buttons do not fire mouse events, so the tooltip lives on
    // the wrapper. pointer-events-none lets hover reach that wrapper.
    const disabledTooltip = computed(() => {
      if (!props.disabled) {
        return '';
      }
      return props.disabledReason?.trim() || '';
    });

    // 4. Safely resolve the string to a valid Vue Component instance
    const resolvedIcon = computed(() => {
    const iconComponent = icons[props.iconName] as Component | undefined;
    
    // Falls back gracefully to HelpCircle if a missing icon name is requested
    return iconComponent || icons.HelpCircle;
    });
</script>
