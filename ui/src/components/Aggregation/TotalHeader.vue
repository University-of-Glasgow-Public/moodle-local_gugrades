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

        <div class="py-1 font-light normal-case" v-if="reassessment">
            <UBadge variant="success">{{ mstrings.reassessment }}</UBadge>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import UBadge from '@/components/Common/UBadge.vue';
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

    const toggleSorting = computed(() => props.headercontext.column.getToggleSortingHandler());

</script>