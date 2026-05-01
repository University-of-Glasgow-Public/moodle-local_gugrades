<template>
    <!-- Refresh button -->
    <div class="ml-2 tooltip"  @click="refresh_clicked" :data-tip="mstrings['refreshpage']">
        <button class="btn">
            <ArrowPathIcon class="size-6 text-black-500"></ArrowPathIcon>
        </button>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { ArrowPathIcon } from '@heroicons/vue/24/outline'

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