<template>
    <Menu as="div" class="relative inline-block text-left">
        <!-- Menu Trigger Button -->
        <MenuButton class="flex items-center justify-center p-1.5 rounded-full text-slate-500 hover:text-brand-dark-purple hover:bg-brand-light-purple/15 cursor-pointer focus:outline-none focus:ring-2 focus:ring-university-blue transition-colors" aria-label="Open menu">
            <slot name="trigger">
                <EllipsisVertical :size="18" />
            </slot>
        </MenuButton>

        <!-- Dropdown Panel Container -->
        <MenuItems class="absolute z-999 bottom-full left-1/2 -translate-x-1/2 mb-2 w-52 p-1.5 flex flex-col gap-1 rounded-xl bg-slate-100 text-university-blue border border-brand-light-purple/30 shadow-md focus:outline-none">
            <slot></slot>
        </MenuItems>
    </Menu>
</template>

<script setup>
    import { Menu, MenuButton, MenuItems } from '@headlessui/vue'
    import { EllipsisVertical } from 'lucide-vue-next'
</script>

