<template>
    <!-- Refresh button -->
    <div class="tooltip"  @click="refresh_clicked" :data-tip="mstrings['refreshpage']">
        <button class="btn btn-outline mr-2 btn-sm" aria-label="Refresh table">
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