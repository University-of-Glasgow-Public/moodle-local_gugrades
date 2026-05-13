<template>
    <div class="flex">
        <a href="#/" @click="$emit('checked', itemid)" class="plainlink" :style="indentstyle">
            <SquareCheck v-if="checked" tabindex="0"></SquareCheck>
            <Square v-else tabindex="0"></Square>
        </a>
        <span class="badge badge-pill ml-2"  :class="badgeclass">{{ mstrings.reassessment }}?</span>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { SquareCheck, Square } from '@lucide/vue';

    const props = defineProps({
        itemid: Number,
        checkeditemid: Number,
        depth: {
            type: Number,
            required: true
        }
    });

    const bscolors = [
        'primary',
        'secondary',
        'info',
        'danger',
        'warning',
        'success'
    ];
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * Is the box checked?
     */
    const checked = computed(() => props.itemid == props.checkeditemid);

    /**
     * badge class (by color)
     */
    const badgeclass = computed(() => {
        const index = (props.depth - 2) % bscolors.length;

        return 'badge-' + bscolors[index];
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