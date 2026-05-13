<template>
    <!-- Refresh button -->
    <div class="ml-2 tooltip"  @click="refresh_clicked" :data-tip="mstrings['refreshpage']">
        <button class="btn btn-secondary" aria-label="Refresh table">
            <RefreshCcw></RefreshCcw>
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