<template>
    <DebugDisplay :debug="debug"></DebugDisplay>
    <a v-if="props.gradehidden" class="block px-3 py-2.5 text-sm cursor-pointer transition-colors hover:bg-university-blue/10" href="#" @click="showhide('show')">{{ mstrings.show }}</a>
    <a v-if="!props.gradehidden" class="block px-3 py-2.5 text-sm cursor-pointer transition-colors hover:bg-university-blue/10" href="#" @click="showhide('hide')">{{ mstrings.hide }}</a>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
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
        close: Function,
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
            if (props.close) {
                props.close();
            }
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });


    }
</script>