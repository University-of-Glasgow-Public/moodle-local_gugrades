<template>
  <UTooltip :text="title" v-bind="$attrs">
    <button 
      @click.prevent="click_help"
      class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm bg-brand-light-yellow text-brand-dark-red text-xs font-medium hover:brightness-95 transition-all cursor-pointer shadow-xs"
    >
      <CircleQuestionMark :size="14" />
      <span>Help</span>
    </button>
  </UTooltip>


<!-- HeadlessUI Dialog -->
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" class="relative z-50" @close="isOpen = false">

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
                <div class="bg-white/10 rounded-full p-1.5 shrink-0">
                  <CircleHelp :size="18" class="text-white" />
                </div>
                <div class="flex-1">
                  <DialogTitle class="text-sm font-semibold text-white">
                    {{ title }}
                  </DialogTitle>
                  <p class="text-xs text-white/70 mt-0.5">Context help</p>
                </div>
                <button
                  @click="isOpen = false"
                  class="text-white/60 hover:text-white transition-colors cursor-pointer"
                  aria-label="Close"
                >
                  <X :size="18" />
                </button>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 space-y-3">
                <slot>
                  <p class="text-sm text-slate-700 leading-relaxed prose prose-slate" v-html="help">
                  </p>
                </slot>
              </div>

              <!-- Footer -->
              <div class="px-5 py-3 border-t border-slate-100 flex justify-end">
                <button
                  @click="isOpen = false"
                  class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-dark-blue hover:bg-brand-dark-blue/90 rounded-md transition-colors shadow-xs cursor-pointer"
                >
                  Got it
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
    import { CircleQuestionMark, MoveLeft, CircleHelp, X } from '@lucide/vue';
    import { moodleFetch } from '@/js/moodlefetch';
    import UButton from './UButton.vue';
    import UTooltip from './UTooltip.vue';

    const props = defineProps<{
        title: string,
        subject: string,
    }>()

    const isOpen = ref(false);
    const help = ref('');

    /**
     * Click help button
     */
    function click_help() {
        moodleFetch(
            'local_gugrades_get_help',
            {
                subject: props.subject,
            }
        )
        .then((result: any) => {
            help.value = result.help;
            isOpen.value = true;
        })
        .catch((error) => {
          console.error(error);
        });
    }
</script>
