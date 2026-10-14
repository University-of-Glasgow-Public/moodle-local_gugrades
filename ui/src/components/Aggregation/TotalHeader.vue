<template>
    <div class="w-full max-w-44 text-[11px] leading-tight">
        <div class="mb-1 flex h-7 items-center justify-center text-sm font-bold">{{ shortname }}</div>
        <div class="font-normal text-white/80">
            <span :class="formattedatype === mstrings.seewarnings ? 'inline-block rounded bg-brand-light-yellow px-1.5 py-0.5 font-medium text-university-blue' : ''">{{ formattedatype }}</span>
        </div>
        <div class="font-normal text-white/80" v-if="strategy"><i>{{ strategy }}</i></div>
        <div class="font-normal text-white/80" v-if="reassessment">{{ mstrings.reassessment }}</div>
    </div>
</template>

<script setup lang="ts">
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { HeaderContext } from '@tanstack/vue-table';

    interface iProps {
        shortname: string;
        strategy: string;
        formattedatype: string;
        reassessment: boolean;
        headercontext: HeaderContext<any, any>;
    }

    const props = defineProps< iProps >();

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

</script>