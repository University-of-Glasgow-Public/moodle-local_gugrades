<template>
  <button 
    type="button" 
    class="inline-flex items-center gap-1 px-2 py-0.5 w-max text-xs font-semibold rounded-full border transition-colors cursor-pointer shrink-0 shadow-xs
           text-[var(--color-brand-dark-purple)] 
           bg-[var(--color-brand-light-purple)] 
           border-[color-mix(in_srgb,var(--color-brand-dark-purple)_20%,transparent)] 
           hover:bg-[color-mix(in_srgb,var(--color-brand-light-purple)_85%,black)]"
  >
    <HelpCircle class="w-3.5 h-3.5 shrink-0" />
    <span class="leading-none">Help</span>
  </button>
</template>

<script setup lang="ts">
    import { HelpCircle } from '@lucide/vue';
</script>
