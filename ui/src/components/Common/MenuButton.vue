<!-- MenuButton.vue -->
<template>
  <button 
    @click="emit('click', $event)" 
    :disabled="disabled" 
    class="inline-flex items-center justify-center h-8 px-3 rounded-md bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-300 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed w-36 shadow-sm font-semibold text-xs gap-2 transition-all duration-150"
    type="button"
  >
    <!-- Dynamic Component injector rendering the type-safe Lucide component -->
    <component 
      :is="resolvedIcon" 
      :size="16" 
      class="shrink-0 opacity-70" 
      :class="props.warning ? '!text-brand-dark-pink' : ''"
    />
    
    <span class="truncate">
      <slot></slot>
    </span>
  </button>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import * as icons from '@lucide/vue';
    import type { Component } from 'vue';

    // 1. Extract all valid icon export names from Lucide to prevent string typos
    type LucideIconName = keyof typeof icons;

    // 2. Define strict TypeScript Props interface
    interface Props {
      iconName: LucideIconName;
      disabled?: boolean;
      warning?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
    disabled: false
    });

    // 3. Define type-safe Emits
    const emit = defineEmits<{
    (e: 'click', event: MouseEvent): void
    }>();

    // 4. Safely resolve the string to a valid Vue Component instance
    const resolvedIcon = computed(() => {
    const iconComponent = icons[props.iconName] as Component | undefined;
    
    // Falls back gracefully to HelpCircle if a missing icon name is requested
    return iconComponent || icons.HelpCircle;
    });
</script>
