<!-- GradeAlert.vue -->
<template>
  <div 
    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold tracking-wide border shadow-sm select-none"
    :class="activeClasses"
  >
    <!-- Dynamic Indicator Icon -->
    <component :is="activeIcon" :size="12" class="shrink-0" />
    
    <span>
      <slot></slot>
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { AlertCircle, EyeOff, ShieldAlert } from '@lucide/vue'

type AlertVariant = 'discrepancy' | 'gradebook-hidden' | 'grade-hidden'

interface Props {
  variant: AlertVariant
}

const props = defineProps<Props>()

// Complete, static string literals that Tailwind can easily scan and compile
const styleMap: Record<AlertVariant, string> = {
  discrepancy: 'bg-rose-50 text-rose-700 border-rose-200',
  'gradebook-hidden': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  'grade-hidden': 'bg-amber-50 text-amber-800 border-amber-200'
}

const iconMap = {
  discrepancy: ShieldAlert,
  'gradebook-hidden': EyeOff,
  'grade-hidden': AlertCircle
}

const activeClasses = computed(() => styleMap[props.variant])
const activeIcon = computed(() => iconMap[props.variant])
</script>
