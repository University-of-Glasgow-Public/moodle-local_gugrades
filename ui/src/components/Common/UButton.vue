
<template>
  <component
    :is="as"
    :class="classes"
    :type="as === 'button' ? (($attrs.type as string) || 'button') : undefined"
    :disabled="as === 'button' ? isDisabled : undefined"
    :aria-disabled="isDisabled || undefined"
    :aria-busy="loading || undefined"
  >
    <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot />
  </component>
</template>

<script setup lang="ts">
/**
 * UButton — drop-in replacement for DaisyUI's `btn` usage pattern,
 * but as a Vue component using the project's Tailwind @theme tokens
 * (university-blue, brand-dark-blue, brand-dark-purple, etc.)
 * instead of a global `.btn` CSS class.
 *
 * <UButton variant="primary">Save</UButton>
 * <UButton variant="error" appearance="outline" size="sm">Delete</UButton>
 * <UButton as="router-link" to="/students" appearance="ghost">Students</UButton>
 * <UButton shape="circle" variant="neutral"><TrashIcon class="size-4" /></UButton>
 *
 * Note on Tailwind's JIT scanner: the class maps below are written as
 * full literal strings (e.g. 'bg-university-blue text-white'), not
 * assembled from fragments like `bg-${variant}`. Tailwind only ever
 * looks for literal class-name-shaped text in source files, so
 * fragment-built classes would silently fail to generate.
 *
 * Trade-off vs the JS version: swapping the runtime prop validators
 * for union types gets you autocomplete and compile-time checking in
 * .vue/.ts callers, but a plain JS caller passing an invalid string
 * at runtime will no longer be caught. Worth keeping in mind if any
 * part of the app still calls this from un-typed code.
 */
import { computed, type Component } from 'vue'

type ButtonVariant = 'default' | 'primary' | 'secondary' | 'accent' | 'neutral' | 'success' | 'error' | 'warning' | 'info'
type ButtonAppearance = 'solid' | 'outline' | 'ghost' | 'link'
type ButtonSize = 'xs' | 'sm' | 'md' | 'lg'
type ButtonShape = 'circle' | 'square'

interface Props {
  as?: string | Component
  variant?: ButtonVariant
  appearance?: ButtonAppearance
  size?: ButtonSize
  shape?: ButtonShape | null
  block?: boolean
  wide?: boolean
  disabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
  variant: 'default',
  appearance: 'solid',
  size: 'md',
  shape: null,
  block: false,
  wide: false,
  disabled: false,
  loading: false,
})

// Branded charcoal for "neutral", same color-mix used in university-theme.css,
// kept as an arbitrary Tailwind value so it stays a true brand derivative
// rather than a generic slate.
const NEUTRAL = 'color-mix(in_srgb,var(--color-university-blue)_35%,#1e293b)'

// Solid fill: background + text + border, per variant.
// Record<ButtonVariant, string> means TS will error if a variant is
// ever added to the union above and forgotten here.
const SOLID_FILL: Record<ButtonVariant, string> = {
  default:   'bg-white text-slate-800 border-university-blue/20',
  primary:   'bg-university-blue text-white border-university-blue',
  secondary: 'bg-brand-dark-blue text-white border-brand-dark-blue',
  accent:    'bg-brand-dark-purple text-white border-brand-dark-purple',
  neutral:   `bg-[${NEUTRAL}] text-white border-[${NEUTRAL}]`,
  success:   'bg-brand-dark-green text-white border-brand-dark-green',
  error:     'bg-brand-dark-red text-white border-brand-dark-red',
  warning:   'bg-brand-light-yellow text-university-blue border-university-blue/20',
  info:      'bg-brand-light-blue text-university-blue border-university-blue/20',
}

// Outline / ghost / link: text + border + hover/active tint, per variant.
// Warning and info deliberately borrow university-blue here rather than
// their own (pale) fill color, since pale-on-white text isn't legible.
const ACCENT: Record<ButtonVariant, string> = {
  default:   'text-university-blue border-university-blue hover:bg-university-blue/10 active:bg-university-blue/20',
  primary:   'text-university-blue border-university-blue hover:bg-university-blue/10 active:bg-university-blue/20',
  secondary: 'text-brand-dark-blue border-brand-dark-blue hover:bg-brand-dark-blue/10 active:bg-brand-dark-blue/20',
  accent:    'text-brand-dark-purple border-brand-dark-purple hover:bg-brand-dark-purple/10 active:bg-brand-dark-purple/20',
  neutral:   `text-[${NEUTRAL}] border-[${NEUTRAL}] hover:bg-[${NEUTRAL}]/10 active:bg-[${NEUTRAL}]/20`,
  success:   'text-brand-dark-green border-brand-dark-green hover:bg-brand-dark-green/10 active:bg-brand-dark-green/20',
  error:     'text-brand-dark-red border-brand-dark-red hover:bg-brand-dark-red/10 active:bg-brand-dark-red/20',
  warning:   'text-university-blue border-university-blue hover:bg-university-blue/10 active:bg-university-blue/20',
  info:      'text-university-blue border-university-blue hover:bg-university-blue/10 active:bg-university-blue/20',
}

interface SizeTokens {
  h: string
  px: string
  text: string
  gap: string
  radius: string
  square: string
}

const SIZE: Record<ButtonSize, SizeTokens> = {
  xs: { h: 'h-[26px]', px: 'px-[10px]', text: 'text-xs', gap: 'gap-1.5', radius: 'rounded-md', square: 'w-[26px]' },
  sm: { h: 'h-8', px: 'px-[14px]', text: 'text-[13px]', gap: 'gap-1.5', radius: 'rounded-md', square: 'w-8' },
  md: { h: 'h-10', px: 'px-[18px]', text: 'text-sm', gap: 'gap-2', radius: 'rounded-lg', square: 'w-10' },
  lg: { h: 'h-12', px: 'px-6', text: 'text-[15px]', gap: 'gap-2.5', radius: 'rounded-xl', square: 'w-12' },
}

const isDisabled = computed(() => props.disabled || props.loading)

const classes = computed<string[]>(() => {
  const s = SIZE[props.size]
  const isLink = props.appearance === 'link'

  const base: string[] = [
    'inline-flex items-center justify-center font-semibold leading-none whitespace-nowrap select-none',
    'transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-dark-blue',
    s.text, s.gap,
  ]

  if (isDisabled.value) {
    base.push('opacity-45 cursor-not-allowed pointer-events-none')
  } else {
    base.push('active:scale-[0.98]')
  }

  if (isLink) {
    base.push('bg-transparent border-0 underline p-0 h-auto', ACCENT[props.variant], 'hover:opacity-75 active:opacity-60')
    return base
  }

  base.push(s.h, s.radius)
  base.push(props.shape ? `p-0 ${s.square}` : s.px)
  if (props.shape === 'circle') base.push('rounded-full')

  if (props.appearance === 'solid') {
    base.push('border', SOLID_FILL[props.variant])
    if (!isDisabled.value) base.push('hover:brightness-90 active:brightness-75')
  } else if (props.appearance === 'outline') {
    base.push('border bg-transparent', ACCENT[props.variant])
  } else if (props.appearance === 'ghost') {
    base.push('border-transparent bg-transparent', ACCENT[props.variant])
  }

  if (props.block) base.push('flex w-full')
  if (props.wide) base.push('min-w-48')

  return base
})
</script>


