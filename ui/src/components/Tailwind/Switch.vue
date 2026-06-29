<!-- AppToggle.vue -->
<template>
    <SwitchGroup as="div" class="flex items-center gap-3">
        <!-- Label Element -->
        <SwitchLabel 
            v-if="label" 
            :class="[
                disabled ? 'text-slate-400 cursor-not-allowed' : 'text-slate-700 cursor-pointer',
                'text-sm font-medium select-none'
            ]"
        >
            {{ label }}
        </SwitchLabel>

        <!-- Pure Tailwind Sliding Switch Track -->
        <Switch
            v-model="model"
            :disabled="disabled"
            :class="[
                // Track colour logic handling active, inactive, and disabled states
                disabled 
                    ? (model ? 'bg-slate-300 border-transparent' : 'bg-slate-100 border-slate-200')
                    : (model ? 'bg-university-blue border-transparent' : 'bg-slate-50 border-slate-400 hover:border-slate-500'),
                
                disabled ? 'cursor-not-allowed' : 'cursor-pointer',
                size === 'sm' ? 'h-5 w-9' : size === 'lg' ? 'h-7 w-14' : 'h-6 w-11',
                'relative inline-flex shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-university-blue focus:ring-offset-2'
            ]"
        >
            <!-- Sliding Circular Knob -->
            <span
                :class="[
                    model ? (size === 'sm' ? 'translate-x-4' : size === 'lg' ? 'translate-x-7' : 'translate-x-5') : 'translate-x-0',
                    size === 'sm' ? 'h-4 w-4' : size === 'lg' ? 'h-6 w-6' : 'h-5 w-5',
                    
                    // Knob colour logic for disabled states
                    disabled
                        ? (model ? 'bg-slate-100' : 'bg-slate-200 border-transparent')
                        : (model ? 'bg-white' : 'bg-white border border-slate-300 shadow-sm'),
                    
                    'pointer-events-none inline-block rounded-full transform ring-0 transition duration-200 ease-in-out'
                ]"
            />
        </Switch>
    </SwitchGroup>
</template>


<script setup lang="ts">
    import { watch, ref, onMounted } from 'vue';
    import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue';

    const props = withDefaults(
        defineProps<{
            active?: boolean;
            disabled?: boolean;
            label?: string;
            size?: 'sm' | 'md' | 'lg';
        }>(),
        {
            disabled: false,
            size: 'md',
        }
    );

    // One line defines the prop, the internal ref, and the parent update sync emit
    //const model = defineModel<boolean>({ default: false });
    const model = ref(false);

    const emit = defineEmits<{
        change: [value: 'on' | 'off']
    }>();

    watch(
        () => props.active,
        (newVal) => {
            model.value = newVal;
        }
    );

    // Use a simple watch to fire your legacy backend 'on'/'off' change event
    watch(model, (val) => {
        emit('change', val ? 'on' : 'off')
    });
</script>
