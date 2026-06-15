<template>
    <!-- Refresh button -->
    <div class="tooltip"  @click="refresh_clicked" :data-tip="mstrings['refreshpage']">
        <button class="inline-flex items-center justify-center h-8 px-3 rounded-md bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-300 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed shadow-sm font-semibold text-xs gap-2 transition-all duration-150" aria-label="Refresh table">
            <RefreshCcw :size="18"></RefreshCcw>
        </button>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { RefreshCcw, RefreshCw } from '@lucide/vue';

    const emit = defineEmits(['refreshtable']);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Refresh button clicked
     */
    function refresh_clicked() {
        emit('refreshtable');
    }
</script>