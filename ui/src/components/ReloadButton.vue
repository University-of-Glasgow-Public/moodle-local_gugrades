<template>
    <!-- Refresh button -->
    <div href="#" id="refresh_button" class="tw:ml-3 tw:tooltip" data-toggle="tooltip" :data-tip="mstrings.refreshpage" @click="refresh_clicked">
        <i class="fa fa-refresh fa-Xl align-middle" :class="[fasizeclass]" aria-hidden="true"></i>
    </div>
</template>

<script setup>
    import {inject, defineEmits, onMounted} from '@vue/runtime-core';

    const mstrings = inject('mstrings');
    const emit = defineEmits(['refreshtable']);

    /**
     * Refresh button clicked
     */
    function refresh_clicked() {
        emit('refreshtable');
    }

    /**
     * Initialise tooltip
     */
     onMounted(() => {
        return;

        // Bodge to get jQuery needed for Bootstrap JS.
        const $ = window.jQuery;

        $(function () {
            $('#refresh_button').tooltip()
        });
    });

</script>