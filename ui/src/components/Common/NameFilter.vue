<template>
    <div class="mt-4">
        <div>
            <div>
                <InitialBar :selected="first" :label="mstrings.firstname" @selected="first_selected"></InitialBar>
                <InitialBar :selected="last" :label="mstrings.lastname" @selected="last_selected"></InitialBar>
            </div>
        </div>
        <div v-if="showreset">
            <button class="btn btn-primary btn-small" @click="reset_filter">{{ mstrings.resetfilter }}</button>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref} from 'vue';
    import InitialBar from '@/components/Common/InitialBar.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    const emit = defineEmits(['selected']);

    const first = ref('all');
    const last = ref('all');
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const showreset = ref(false);

    defineExpose({
        reset_filter,
    });

    /**
     * Process letter selected in one of the bars
     */
    function first_selected(letter: string) {
        first.value = letter;
        showreset.value = (first.value != 'all') || (last.value != 'all');
        emit('selected', first.value, last.value);
    }

    function last_selected(letter: string) {
        last.value = letter;
        showreset.value = (first.value != 'all') || (last.value != 'all');
        emit('selected', first.value, last.value);
    }

    /**
     * Reset filter back to all/all
     */
    function reset_filter() {
        first.value = 'all';
        last.value = 'all';
    }

</script>