<template>
    <DebugDisplay :debug="debug"></DebugDisplay>
    <a v-if="props.gradehidden" class="dropdown-item" href="#" @click="showhide('show')">{{ mstrings.show }}</a>
    <a v-if="!props.gradehidden" class="dropdown-item" href="#" @click="showhide('hide')">{{ mstrings.hide }}</a>
</template>

<script setup lang="ts">
    import {inject, ref} from '@vue/runtime-core';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        courseid: Number,
        itemid: Number,
        userid: Number,
        gradehidden: Boolean,
    });

    const emit = defineEmits(['changed']);

    /**
     * Hide/show button clicked
     */
    function showhide(action: string) {
        moodleFetch(
            'local_gugrades_show_hide_grade',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
                hide: action == 'hide',
            }
        )
        .then(() => {
            emit('changed');
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });
    }
</script>