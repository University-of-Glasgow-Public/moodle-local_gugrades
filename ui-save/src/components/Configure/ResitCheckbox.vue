<template>
    <a href="#/" @click="$emit('checked', itemid)" class="plainlink" :style="indentstyle">
        <i v-if="checked" class="fa fa-check-square-o" aria-hidden="true"></i>
        <i v-else class="fa fa-square-o" aria-hidden="true"></i>
    </a>
    <span class="badge badge-pill ml-2"  :class="badgeclass">{{ mstrings.reassessment }}?</span>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';

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