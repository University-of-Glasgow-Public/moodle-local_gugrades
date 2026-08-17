<template>
  <TransitionRoot appear :show="modelValue" as="template">
    <Dialog as="div" class="relative z-50" @close="close">

      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="duration-200 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-150 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" />
      </TransitionChild>

      <!-- Modal positioner -->
      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">

          <TransitionChild
            as="template"
            enter="duration-200 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-150 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden">

              <!-- Header -->
              <div class="bg-brand-dark-blue px-5 py-4 flex items-center gap-3">
                <slot name="header" :close="close">
                  <div class="flex-1">
                    <DialogTitle class="text-sm font-semibold text-white">
                      {{ title }}
                    </DialogTitle>
                    <p v-if="subtitle" class="text-xs text-white/70 mt-0.5">{{ subtitle }}</p>
                  </div>
                  <button
                    v-if="showheaderclose"
                    @click="close"
                    class="text-white/60 hover:text-white transition-colors cursor-pointer"
                    aria-label="Close"
                  >
                    <X :size="18" />
                  </button>
                </slot>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 space-y-3">
                <slot />
              </div>

              <!-- Footer -->
              <div class="px-5 py-3 border-t border-slate-100 flex justify-end">
                <slot name="footer" :close="close">
                  <button
                    @click="close"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-dark-blue hover:bg-brand-dark-blue/90 rounded-md transition-colors shadow-xs cursor-pointer"
                  >
                    Close
                  </button>
                </slot>
              </div>

            </DialogPanel>
          </TransitionChild>
        </div>
      </div>

    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
    import {
        Dialog,
        DialogPanel,
        DialogTitle,
        TransitionRoot,
        TransitionChild,
    } from '@headlessui/vue';
    import { X } from '@lucide/vue';

    withDefaults(
        defineProps<{
            modelValue: boolean;
            showheaderclose?: boolean;
            title?: string;
            subtitle?: string;
        }>(),
        { showheaderclose: true, title: '', subtitle: '' }
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', value: boolean): void;
        (e: 'close'): void;
    }>();

    function close() {
        emit('update:modelValue', false);
        emit('close');
    }
</script>