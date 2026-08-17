<template>
    <div>
        <div>
            <span @click="toggleSorting" class="cursor-pointer">{{ shortname  }}</span>
        </div>

        <div>
            {{ formattedatype }}
        </div>

        <div class="py-1 font-light normal-case" v-if="strategy">
            <i>{{ strategy }}</i>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { HeaderContext } from '@tanstack/vue-table';

    interface iProps {
        shortname: string;
        strategy: string;
        formattedatype: string;
        headercontext: HeaderContext<any, any>;
    }

    const props = defineProps< iProps >();

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const toggleSorting = computed(() => props.headercontext.column.getToggleSortingHandler());

</script>