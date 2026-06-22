<!-- AppToggle.vue -->
<template>
    <SwitchGroup as="div" class="flex items-center gap-3">
        <!-- Label Element -->
        <SwitchLabel v-if="label" class="text-sm font-medium text-slate-700 select-none cursor-pointer">
            {{ label }}
        </SwitchLabel>

        <!-- Pure Tailwind Sliding Switch Track -->
        <Switch
            v-model="model"
            :disabled="disabled"
            :class="[
                model 
                    ? 'bg-university-blue border-transparent' 
                    : 'bg-slate-50 border-slate-400 hover:border-slate-500',
                disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                size === 'sm' ? 'h-5 w-9' : size === 'lg' ? 'h-7 w-14' : 'h-6 w-11',
                'relative inline-flex shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-university-blue focus:ring-offset-2'
            ]"
        >
            <!-- Sliding Circular Knob -->
            <span
                :class="[
                    model ? (size === 'sm' ? 'translate-x-4' : size === 'lg' ? 'translate-x-7' : 'translate-x-5') : 'translate-x-0',
                    size === 'sm' ? 'h-4 w-4' : size === 'lg' ? 'h-6 w-6' : 'h-5 w-5',
                    model ? 'bg-white' : 'bg-white border border-slate-300 shadow-sm',
                    'pointer-events-none inline-block rounded-full transform ring-0 transition duration-200 ease-in-out'
                ]"
            />
        </Switch>
    </SwitchGroup>
</template>

<script setup lang="ts">
    import { watch } from 'vue';
    import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue';

    const props = withDefaults(
        defineProps<{
            disabled?: boolean
            label?: string
            size?: 'sm' | 'md' | 'lg'
        }>(),
        {
            disabled: false,
            size: 'md',
        }
    );

    // One line defines the prop, the internal ref, and the parent update sync emit
    const model = defineModel<boolean>({ default: false });

    const emit = defineEmits<{
        change: [value: 'on' | 'off']
    }>();

    // Use a simple watch to fire your legacy backend 'on'/'off' change event
    watch(model, (val) => {
        emit('change', val ? 'on' : 'off')
    });
</script>
