<template>

<!-- HeadlessUI Dialog -->
  <TransitionRoot appear :show="props.isopen" as="template">
    <Dialog as="div" class="relative z-50" :initial-focus="initialFocusEl" @close="emits('closed')">

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
        <div class="fixed inset-0 bg-black/30" />
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
            <DialogPanel class="w-full max-w-md bg-base-100 rounded-xl shadow-xl overflow-hidden">

              <span ref="initialFocusEl" tabindex="-1" class="sr-only" />

              <!-- Header -->
              <div class="bg-primary px-5 py-4 flex items-center gap-3">
                <div class="bg-primary-content/20 rounded-full p-1.5 shrink-0">
                  <CircleHelp :size="18" class="text-primary-content" />
                </div>
                <div class="flex-1">
                  <DialogTitle class="text-sm font-semibold text-primary-content">
                    <slot name="title">Title</slot>
                  </DialogTitle>
                  <p class="text-xs text-primary-content/70 mt-0.5"><slot name="tagline"></slot></p>
                </div>
                <button
                  @click="emits('closed')"
                  class="text-primary-content/60 hover:text-primary-content transition-colors"
                  aria-label="Close"
                >
                  <X :size="18" />
                </button>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 space-y-3">
                <slot>
                  <p class="text-sm text-base-content leading-relaxed prose">
                  </p>
                </slot>
              </div>

              <!-- Footer -->
              <div class="px-5 py-3 border-t border-base-200 flex justify-end">
                <button
                  @click="emits('closed')"
                  class="btn btn-primary btn-sm"
                >
                  Close
                </button>
              </div>

            </DialogPanel>
          </TransitionChild>
        </div>
      </div>

    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import {
      Dialog,
      DialogPanel,
      DialogTitle,
      TransitionRoot,
      TransitionChild,
    } from '@headlessui/vue';

    const initialFocusEl = ref<HTMLElement | null>(null);

    const props = defineProps<{
        isopen?: boolean;
    }>();

    const emits = defineEmits(['closed']);
</script>