<template>
    <div class="mt-4">
        <div>
            <div>
                <InitialBar :selected="firstname" :label="mstrings.firstname" @selected="first_selected"></InitialBar>
                <InitialBar :selected="lastname" :label="mstrings.lastname" @selected="last_selected"></InitialBar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import InitialBar from '@/components/Common/InitialBar.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { useFilter } from '@/stores/filter';

    const emit = defineEmits(['selected']);

    const filterstore = useFilter();
    const { firstname, lastname } = storeToRefs( filterstore );
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Process letter selected in one of the bars
     */
    function first_selected(letter: string) {
        firstname.value = letter;
        emit('selected', firstname.value, lastname.value);
    }

    function last_selected(letter: string) {
        lastname.value = letter;
        emit('selected', firstname.value, lastname.value);
    }

</script>