
<template>
  <div v-if="visible" :class="classes" role="alert">
    <!--
    <slot name="icon">
      <span :class="['mt-0.5 font-bold leading-none', ICON_COLOR[variant]]" aria-hidden="true">
        {{ ICON_GLYPH[variant] }}
      </span>
    </slot>
    -->

    <div class="flex-1">
      <p v-if="title" class="mb-0.5 font-semibold">{{ title }}</p>
      <slot />
    </div>

    <button
      v-if="dismissible"
      type="button"
      class="shrink-0 opacity-60 transition hover:opacity-100"
      aria-label="Dismiss"
      @click="dismiss"
    >
      ✕
    </button>
  </div>
</template>

<script setup lang="ts">
/**
 * UAlert — drop-in replacement for DaisyUI's `alert` usage pattern,
 * built on the same brand-derived tints/accents as UButton and the
 * EasyDataTable theme.
 *
 * <UAlert variant="success">Grades published.</UAlert>
 * <UAlert variant="error" title="Save failed" dismissible>
 *   Check the ID number format and try again.
 * </UAlert>
 */
import { ref, computed } from 'vue'

type AlertVariant = 'neutral' | 'info' | 'success' | 'warning' | 'error'

interface Props {
  variant?: AlertVariant
  title?: string
  dismissible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'info',
  title: '',
  dismissible: false,
})

const emit = defineEmits<{ dismiss: [] }>()

const visible = ref(true)
function dismiss() {
  visible.value = false
  emit('dismiss')
}

// Same "navy at 75% into black" trick used for the warning button text,
// so text stays legible on the pale yellow fill.
const WARNING_TEXT = 'color-mix(in_srgb,var(--color-university-blue)_75%,black)'

// bg tint + soft frame on three sides + a solid 4px accent stripe on the
// left. Per-side border classes (rather than one shorthand border-color)
// so the stripe's full-strength color can't lose a cascade-order tie
// against the softer frame color.
const STYLES: Record<AlertVariant, string> = {
  neutral: 'bg-university-blue/5 border-t border-r border-b border-t-university-blue/25 border-r-university-blue/25 border-b-university-blue/25 border-l-4 border-l-university-blue text-slate-800',
  info:    'bg-brand-light-blue/15 border-t border-r border-b border-t-brand-light-blue/25 border-r-brand-light-blue/25 border-b-brand-light-blue/25 border-l-4 border-l-brand-light-blue text-university-blue',
  success: 'bg-brand-dark-green/10 border-t border-r border-b border-t-brand-dark-green/25 border-r-brand-dark-green/25 border-b-brand-dark-green/25 border-l-4 border-l-brand-dark-green text-brand-dark-green',
  warning: `bg-brand-light-yellow/25 border-t border-r border-b border-t-university-blue/20 border-r-university-blue/20 border-b-university-blue/20 border-l-4 border-l-brand-light-yellow text-[${WARNING_TEXT}]`,
  error:   'bg-brand-dark-red/10 border-t border-r border-b border-t-brand-dark-red/25 border-r-brand-dark-red/25 border-b-brand-dark-red/25 border-l-4 border-l-brand-dark-red text-brand-dark-red',
}

const ICON_COLOR: Record<AlertVariant, string> = {
  neutral: 'text-university-blue',
  info: 'text-brand-light-blue',
  success: 'text-brand-dark-green',
  warning: `text-[${WARNING_TEXT}]`,
  error: 'text-brand-dark-red',
}

const ICON_GLYPH: Record<AlertVariant, string> = { neutral: 'ℹ', info: 'ℹ', success: '✓', warning: '!', error: '✕' }

const classes = computed<string[]>(() => [
  'flex items-start gap-3 rounded-lg px-4 py-3.5 text-sm leading-relaxed',
  STYLES[props.variant],
])
</script>


