<template>
    <div class="flex">
        <a href="#/" @click="$emit('checked', itemid)" class="plainlink" :style="indentstyle">
            <SquareCheck v-if="checked" tabindex="0"></SquareCheck>
            <Square v-else tabindex="0"></Square>
        </a>
        <UBadge class="ml-2" :variant="badgevariant">{{ mstrings.reassessment }}?</UBadge>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { SquareCheck, Square } from '@lucide/vue';
    import UBadge from '../Common/UBadge.vue';

    const props = defineProps({
        itemid: Number,
        checkeditemid: Number,
        depth: {
            type: Number,
            required: true
        }
    });

    const badgevariants = [
        'primary',
        'secondary',
        'info',
        'danger',
        'warning',
        'success'
    ] as const;
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Is the box checked?
     */
    const checked = computed(() => props.itemid == props.checkeditemid);

    /**
     * badge class (by color)
     */
    const badgevariant = computed(() => {
        const index = (props.depth - 2) % badgevariants.length;

        return badgevariants[index] ?? 'neutral';
    })

    /**
     * Get indent/padding class
     */
    const indentstyle = computed(() => {
        const padding = (props.depth - 1) * 20;

        return {
            'padding-left': padding + 'px',
        }
    });

</script>

<style>
    .plainlink, .plainlink:hover, .plainlink:visited, .plainlink:link, .plainlink:active {
        text-decoration: none;
    }
</style>