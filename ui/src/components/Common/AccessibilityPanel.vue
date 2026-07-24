<template>
  <div v-if="enabled">
    <!-- Floating launcher button (lives outside #mygrades_container so it is
         never affected by the accessibility theme filters). -->
    <button
        type="button"
        class="a11y-launcher fixed bottom-5 right-5 z-[9998] flex items-center gap-2 rounded-full bg-university-blue px-4 py-3 text-white shadow-lg hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-university-blue/40 transition-all"
        :aria-expanded="isOpen"
        aria-haspopup="dialog"
        aria-label="Accessibility options"
        @click="isOpen = true"
    >
        <Accessibility :size="22" />
        <span class="hidden sm:inline text-sm font-semibold">Accessibility</span>
    </button>

    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="a11y-panel relative z-[9999]" @close="isOpen = false">
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

            <!-- Slide-over panel -->
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-y-0 right-0 flex max-w-full">
                    <TransitionChild
                        as="template"
                        enter="transform transition ease-out duration-250"
                        enter-from="translate-x-full"
                        enter-to="translate-x-0"
                        leave="transform transition ease-in duration-200"
                        leave-from="translate-x-0"
                        leave-to="translate-x-full"
                    >
                        <DialogPanel class="w-screen max-w-sm bg-white shadow-xl flex flex-col h-full">
                            <!-- Header -->
                            <div class="bg-university-blue px-5 py-4 flex items-center gap-3 shrink-0">
                                <div class="bg-white/10 rounded-full p-1.5 shrink-0">
                                    <Accessibility :size="20" class="text-white" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle class="text-sm font-semibold text-white">
                                        Accessibility options
                                    </DialogTitle>
                                    <p class="text-xs text-white/70 mt-0.5">
                                        Adjust how MyGrades looks to suit your needs
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="text-white/60 hover:text-white transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-white rounded"
                                    aria-label="Close accessibility options"
                                    @click="isOpen = false"
                                >
                                    <X :size="20" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-6">
                                <!-- Theme presets -->
                                <fieldset>
                                    <legend class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">
                                        Display theme
                                    </legend>
                                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Display theme">
                                        <button
                                            v-for="t in themes"
                                            :key="t.id"
                                            type="button"
                                            role="radio"
                                            :aria-checked="settings.theme === t.id"
                                            class="flex flex-col items-center gap-1.5 rounded-lg border-2 px-3 py-3 text-xs font-medium transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-university-blue"
                                            :class="settings.theme === t.id
                                                ? 'border-university-blue bg-university-blue/5 text-university-blue'
                                                : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                            @click="setTheme(t.id)"
                                        >
                                            <component :is="t.icon" :size="20" />
                                            <span>{{ t.label }}</span>
                                        </button>
                                    </div>
                                </fieldset>

                                <!-- Text size -->
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">
                                        Text size
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-university-blue"
                                            aria-label="Decrease text size"
                                            :disabled="settings.fontScale <= MIN_FONT_SCALE"
                                            @click="decreaseFont"
                                        >
                                            <Minus :size="18" />
                                        </button>
                                        <div class="flex-1 text-center">
                                            <div class="text-lg font-semibold text-slate-800">
                                                {{ Math.round(settings.fontScale * 100) }}%
                                            </div>
                                            <button
                                                type="button"
                                                class="text-xs text-university-blue hover:underline focus:outline-none focus:underline"
                                                @click="resetFont"
                                            >
                                                Reset
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-university-blue"
                                            aria-label="Increase text size"
                                            :disabled="settings.fontScale >= MAX_FONT_SCALE"
                                            @click="increaseFont"
                                        >
                                            <Plus :size="18" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Reading adjustments -->
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">
                                        Reading adjustments
                                    </div>
                                    <ul class="space-y-1">
                                        <li
                                            v-for="toggle in toggles"
                                            :key="toggle.key"
                                            class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 hover:bg-slate-50"
                                        >
                                            <label :for="`a11y-${toggle.key}`" class="flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer select-none">
                                                <component :is="toggle.icon" :size="18" class="text-slate-400 shrink-0" />
                                                {{ toggle.label }}
                                            </label>
                                            <button
                                                :id="`a11y-${toggle.key}`"
                                                type="button"
                                                role="switch"
                                                :aria-checked="settings[toggle.key]"
                                                class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-university-blue focus:ring-offset-2 cursor-pointer"
                                                :class="settings[toggle.key] ? 'bg-university-blue' : 'bg-slate-300'"
                                                @click="settings[toggle.key] = !settings[toggle.key]"
                                            >
                                                <span
                                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                                    :class="settings[toggle.key] ? 'translate-x-5' : 'translate-x-0'"
                                                />
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 focus:outline-none focus:underline"
                                    @click="reset"
                                >
                                    <RotateCcw :size="14" />
                                    Reset all
                                </button>
                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-semibold text-white bg-university-blue hover:brightness-110 rounded-md transition-all shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-university-blue focus:ring-offset-2"
                                    @click="isOpen = false"
                                >
                                    Done
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import {
        Dialog,
        DialogPanel,
        DialogTitle,
        TransitionRoot,
        TransitionChild,
    } from '@headlessui/vue';
    import {
        Accessibility,
        X,
        Plus,
        Minus,
        Sun,
        Moon,
        Contrast,
        BookOpen,
        Type,
        Link,
        Eye,
        RotateCcw,
    } from '@lucide/vue';
    import {
        useAccessibility,
        clearAppliedAccessibility,
        setAccessibilityEnabledCache,
        hasStoredAccessibilitySettings,
        MIN_FONT_SCALE,
        MAX_FONT_SCALE,
        type A11yTheme,
        type IA11ySettings,
    } from '@/stores/accessibility';

    const store = useAccessibility();
    const { settings } = storeToRefs(store);
    const { activate, importFromHillhead, setTheme, increaseFont, decreaseFont, resetFont, reset } = store;

    const isOpen = ref(false);

    // Whether the tool is enabled site-wide (admin setting). Hidden until we
    // know, so it never flashes in when disabled.
    const enabled = ref(false);

    onMounted(() => {
        moodleFetch('local_gugrades_get_accessibility_enabled', {})
            .then((result: any) => {
                enabled.value = !!result.enabled;
                // Remember for the next load so we can decide up front whether
                // to bootstrap (enabled) or stay completely inert (disabled).
                setAccessibilityEnabledCache(enabled.value);

                if (enabled.value) {
                    // Prefer existing MyGrades prefs. If the user has never
                    // configured MyGrades accessibility but does have Hillhead
                    // settings, seed from those so the tools feel continuous.
                    if (!hasStoredAccessibilitySettings() && result.hillhead?.hassettings) {
                        importFromHillhead(result.hillhead);
                    } else {
                        activate();
                    }
                } else {
                    // Disabled: behave as if the tool doesn't exist — strip
                    // anything a previous (cached-enabled) load may have applied.
                    clearAppliedAccessibility();
                }
            })
            .catch((error) => {
                // On error, fail safe by keeping the tool hidden and inert.
                console.error(error);
                enabled.value = false;
                setAccessibilityEnabledCache(false);
                clearAppliedAccessibility();
            });
    });

    const themes: { id: A11yTheme; label: string; icon: unknown }[] = [
        { id: 'default', label: 'Standard', icon: Sun },
        { id: 'contrast', label: 'High contrast', icon: Contrast },
        { id: 'dark', label: 'Dark', icon: Moon },
        { id: 'reading', label: 'Reading', icon: BookOpen },
    ];

    type BooleanSettingKey = {
        [K in keyof IA11ySettings]: IA11ySettings[K] extends boolean ? K : never;
    }[keyof IA11ySettings];

    const toggles: { key: BooleanSettingKey; label: string; icon: unknown }[] = [
        { key: 'dyslexiaFont', label: 'Dyslexia-friendly font', icon: Type },
        { key: 'lineSpacing', label: 'Increase line spacing', icon: BookOpen },
        { key: 'letterSpacing', label: 'Increase letter spacing', icon: Type },
        { key: 'highlightLinks', label: 'Highlight links', icon: Link },
        { key: 'reduceMotion', label: 'Reduce motion', icon: Eye },
    ];
</script>
