<template>
    <div class="mt-4">
        <div>
            <div>
                <InitialBar :selected="firstinitial" :label="mstrings.firstname" @selected="firstInitialSelected"></InitialBar>
                <InitialBar :selected="lastinitial" :label="mstrings.lastname" @selected="lastInitialSelected"></InitialBar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, inject, watch} from 'vue';
    import InitialBar from '@/components/Common/InitialBar.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

    const emit = defineEmits(['firstinitialselected', 'lastinitialselected']);
    const props = defineProps({
        'firstinitial': String,
        'lastinitial': String
    });
    const firstinitial = ref(props.firstinitial);
    const lastinitial = ref(props.lastinitial);
    console.log('NameFilter first:',firstinitial, ' last:', lastinitial);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const reset_filter = inject('resetfilter', false);
    const resetfilter = ref(reset_filter);

    /**
     * Process letter selected in one of the bars
     */
    function firstInitialSelected(letter: string) {
        firstinitial.value = letter;
        emit('firstinitialselected', letter);
    }

    function lastInitialSelected(letter: string) {
        lastinitial.value = letter;
        emit('lastinitialselected', letter);
    }

    watch(resetfilter, () => {
        firstinitial.value = 'all';
        lastinitial.value = 'all';
        resetfilter.value = false;
        emit('firstinitialselected', firstinitial.value);
        emit('lastinitialselected', lastinitial.value);
    });

</script>