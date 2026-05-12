<template>
  <div class="relative inline-block" id="theme-dropdown">
    <!-- trigger button -->
    <button class="btn" @click.stop="toggle">
      Theme
    </button>

    <!-- dropdown -->
    <div
      v-if="open"
      class="absolute right-0 mt-2 w-56 bg-base-100 shadow rounded-box p-2 z-50"
    >
      <button
        v-for="t in themes"
        :key="t"
        class="flex items-center gap-3 p-2 w-full hover:bg-base-200 rounded"
        @click="setTheme(t)"
      >
        <!-- preview -->
        <div :data-theme="t" class="flex gap-1">
          <span class="w-3 h-3 rounded bg-primary"></span>
          <span class="w-3 h-3 rounded bg-secondary"></span>
          <span class="w-3 h-3 rounded bg-accent"></span>
        </div>

        <span class="capitalize">{{ t }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
    import { ref, onMounted, onBeforeUnmount } from 'vue'

    const themes = [
    'light',
    'cupcake',
    'bumblebee',
    'emerald',
    'corporate',
    'valentine',
    'garden',
    'lofi',
    'pastel',
    'fantasy',
    'wireframe',
    'cmyk',
    'autumn',
    'acid',
    'lemonade',
    'winter',
    'nord',
    'silk',
    'dark',
    'dracula',
    'abyss',
    'uofg',
    ]

    const open = ref(false)

    function setTheme(theme: string) {
    document.documentElement.setAttribute('data-theme', theme)
    localStorage.setItem('theme', theme)
    open.value = false
    }

    function toggle() {
    open.value = !open.value
    }

    function handleClickOutside(e: MouseEvent) {
    const el = document.getElementById('theme-dropdown')
    if (el && !el.contains(e.target as Node)) {
        open.value = false
    }
    }

    onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    })

    onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    })
</script>
