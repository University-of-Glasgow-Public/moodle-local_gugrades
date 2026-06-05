<!-- AppToggle.vue -->
<template>
  <SwitchGroup as="div" class="flex items-center gap-3">
    <SwitchLabel v-if="label" class="label-text cursor-pointer select-none">
      {{ label }}
    </SwitchLabel>

    <Switch
      v-model="internalValue"
      :disabled="disabled"
      class="toggle"
      :class="[
        internalValue ? 'toggle-primary' : '',
        disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
        size === 'sm' ? 'toggle-sm' : size === 'lg' ? 'toggle-lg' : '',
      ]"
    />
  </SwitchGroup>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'

const props = withDefaults(
  defineProps<{
    modelValue?: boolean
    disabled?: boolean
    label?: string
    size?: 'sm' | 'md' | 'lg'
  }>(),
  {
    modelValue: false,
    disabled: false,
    size: 'md',
  }
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  change: [value: 'on' | 'off']
}>()

const internalValue = ref(props.modelValue)

watch(
  () => props.modelValue,
  (val) => (internalValue.value = val)
)

watch(internalValue, (val) => {
  emit('update:modelValue', val)
  emit('change', val ? 'on' : 'off')
})
</script>