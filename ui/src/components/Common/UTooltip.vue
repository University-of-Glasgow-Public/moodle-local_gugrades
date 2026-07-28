

<template>
  <div v-if="!text || text.trim() === ''" v-bind="$attrs">
    <slot />
  </div>

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

    <!-- 1. DYNAMIC FLOATING SPEECH BUBBLE CONTAINER -->
    <div 
      class="invisible absolute left-1/2 z-50 w-max max-w-xs -translate-x-1/2 rounded-lg bg-brand-dark-purple px-3 py-2 text-sm font-medium normal-case tracking-normal text-white opacity-0 shadow-lg transition-all duration-150 group-hover:visible group-hover:opacity-100 pointer-events-none"
      :class="[
        position === 'above' ? 'bottom-full mb-3' : 'top-full mt-3'
      ]"
    >
      
      <!-- The text content inside the bubble -->
      {{ text }}

      <!-- 2. DYNAMIC SPEECH BUBBLE ARROW POINTER -->
      <div 
        class="absolute left-1/2 h-2.5 w-2.5 -translate-x-1/2 rotate-45 bg-brand-dark-purple"
        :class="[
          position === 'above' ? 'top-full -translate-y-[5px]' : 'bottom-full translate-y-[5px]'
        ]"
      ></div>
    
    </div>

  </div>
</template>

<script setup lang="ts">
    withDefaults(
      defineProps<{ 
        text?: string | null
        hasUnderline?: boolean
        position?: 'above' | 'below' 
      }>(), 
      {
        hasUnderline: false,
        position: 'above' 
      }
    )
</script>


