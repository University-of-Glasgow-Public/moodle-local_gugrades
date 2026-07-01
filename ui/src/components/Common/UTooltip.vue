

<template>
  <!-- 1. SAFETY CHECK: If text is missing or empty, render a plain container with no hover logic -->
  <div v-if="!text || text.trim() === ''" v-bind="$attrs">
    <slot />
  </div>

  <!-- 2. BRANDED SPEECH BUBBLE TOOLTIP -->
  <div 
    v-else 
    class="group relative inline-block transition-all"
    :class="[
      hasUnderline 
        ? 'cursor-help border-b border-dashed border-brand-light-purple/60 hover:border-brand-light-purple' 
        : 'cursor-default'
    ]"
    v-bind="$attrs"
  >
    
    <!-- This is your wrapped content (e.g. your help button) -->
    <slot />

    <!-- The Floating Speech Bubble Container -->
    <div class="invisible absolute bottom-full left-1/2 z-50 mb-3 w-max max-w-xs -translate-x-1/2 rounded-lg bg-brand-dark-purple px-3 py-2 text-sm font-medium normal-case tracking-normal text-white opacity-0 shadow-lg transition-all duration-150 group-hover:visible group-hover:opacity-100 pointer-events-none">
      
      <!-- The text content inside the bubble -->
      {{ text }}

      <!-- 3. THE SPEECH BUBBLE ARROW POINTER -->
      <div class="absolute top-full left-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-[5px] rotate-45 bg-brand-dark-purple"></div>
    
    </div>

  </div>
</template>

<script setup lang="ts">
    withDefaults(
      defineProps<{ 
        text?: string | null
        hasUnderline?: boolean // New prop to turn underline on/off
      }>(), 
      {
        hasUnderline: false // Default to false so buttons don't get underlined
      }
    )
</script>

