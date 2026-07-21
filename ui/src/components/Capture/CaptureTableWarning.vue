<template>
    <div class="flex flex-wrap gap-1.5 items-center">
        <CaptureWarning v-if="user.alert" variant="discrepancy">
            {{ mstrings.discrepancy }}
        </CaptureWarning>
        
        <CaptureWarning v-if="user.gradebookhidden" variant="gradebook-hidden">
            {{ mstrings.hiddengradebook }}
        </CaptureWarning>
        
        <CaptureWarning v-if="user.gradehidden" variant="grade-hidden">
            {{ mstrings.hiddenmygrades }}
        </CaptureWarning>
    </div>
</template>

<script setup lang="ts">
    import CaptureWarning from './CaptureWarning.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { storeToRefs } from 'pinia';

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    interface iProps {
        user: Record<string, any>;   // Keys are strings, values are anything
    }

    const props = defineProps< iProps >();
</script>