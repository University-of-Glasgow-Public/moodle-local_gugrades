<template>
    <a v-if="caneditgrades" class="cursor-pointer" @click.prevent="resit_clicked(user.id, !user.resitrequired)">
        <span v-if="user.resitrequired" class="badge badge-success">{{ mstrings.yes }}</span>
        <span v-else class="badge badge-secondary badge-soft">{{ mstrings.no }}</span>
    </a>
    <span v-if="!caneditgrades">
        <span v-if="user.resitrequired" class="badge badge-success">{{ mstrings.yes }}</span>
        <span v-else class="badge badge-secondary badge-soft">{{ mstrings.no }}</span>
    </span>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    interface IProps {
        user: Record<string, any>;
        caneditgrades: boolean;
    }

    const props = defineProps< IProps >();

    const emits = defineEmits(['userupdated']);

    /**
     * Resit required 'pill' clicked
     */
    function resit_clicked(userid: number, required: boolean) {

        moodleFetch(
            'local_gugrades_resit_required',
            {
                userid: userid,
                required: required,
            }
        )
        .then(() => {
            emits('userupdated', userid);

        })
        .catch((error) => {
            window.console.error(error);
        });
    }
</script>