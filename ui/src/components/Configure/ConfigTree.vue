<template>
    <tr v-for="item in props.nodes.items" :key="item.id" :class="resitclass">
        <td v-if="resitconfig" class="tw:px-2 resit_select tw:border-none">
            <ResitCheckbox  v-if="!resitfade" :itemid="item.id" :checkeditemid="resititemid" @checked="resit_clicked" :depth="depth"></ResitCheckbox>
        </td>
        <td :style="indentstyle" class="tw:flex tw:justify-start">
            <ConfigTreeIcon :gradeitem="item"></ConfigTreeIcon>
            {{ item.itemname }}
        </td>
        <td class="resit_select px-2" >
            <span v-if="item.id == resititemid" class="badge badge-pill badge-success">{{ mstrings['resitselected'] }}</span>
        </td>
        <td>&nbsp;</td> <!-- holder for strategy -->
        <td>
            {{ item.info.scalename }}
            <span v-if="!item.info.isscale">&nbsp;({{ parseFloat(item.grademax) }})</span>
        </td>
        <td>
            <span v-if="showeights" :style="indentstyle">{{ item.info.weight }}%</span>
        </td>
    </tr>
    <template v-for="category in props.nodes.categories" :key="category.id">
        <tr class="pb-2" :class="{ 'bg-light': category.category.even }">
            <td v-if="resitconfig" class="px-2 resit_select">
                <ResitCheckbox v-if="!resitfade" :itemid="category.category.itemid" :checkeditemid="resititemid" @checked="resit_clicked" :depth="depth"></ResitCheckbox>
            </td>
            <td :style="indentstyle">
                <b>
                    <i v-if="props.depth == 1" class="fa fa-folder icon itemicon" :title="mstrings['gradecategory']" aria-hidden="true"></i>
                    <i v-else class="fa fa-folder-o" :title="mstrings['gradecategory']" aria-hidden="true"></i>
                    {{ category.category.fullname }}
                </b>
            </td>
            <td class="resit_select px-2" >
                <span v-if="category.category.itemid == resititemid" class="badge badge-pill badge-success">{{ mstrings['resitselected'] }}</span>
            </td>
            <td>
                {{ category.category.strategy }}
            </td>
            <td></td> <!-- Holder for scale -->
            <td>
                <span v-if="showeights">{{ category.category.info.weight }}%</span>
            </td>
        </tr>
        <ConfigTree :nodes="category" @activityselected="sub_activity_click" :depth="nextlevel" :resitconfig="resitconfig" :resitfade="!category.category.resitcandidate" @saveerror="handle_saveerror"></ConfigTree>
    </template>
</template>

<script setup lang="ts">
    import {ref, inject, computed, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import ConfigTreeIcon from '@/components/Configure/ConfigTreeIcon.vue';
    import ResitCheckbox from '@/components/Configure/ResitCheckbox.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';

    /**
     * resitconfig = enable display of resit radio boxes etc.
     * resitfade = if above is true then non-resit categories are faded out.
     */
    const props = defineProps({
        nodes: {
            type: Object,
            required: true
        },
        depth: {
            type: Number,
            required: true
        },
        resitconfig: Boolean,
        resitfade: Boolean,
    });

    const emit = defineEmits(['activityselected', 'saverror']);
    const resititemid = ref();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    /**
     * get prop change
     */
    onMounted(() => {
        resititemid.value = props.nodes.category.resititemid;
    });

    /**
     * Pass up save error
     */
    function handle_saveerror(error: object) {
        emit('saverror', error);
    }

    /**
     * Need this to make sure we pass down a number,
     * not a string
     */
    const nextlevel = computed(() => props.depth*1 + 1);

    /**
     * Are we showing weights?
     */
    const showeights = computed(() => props.nodes.category.weighted);

    /**
     * Get resit CSS class
     */
    const resitclass = computed(() => (
        {
            resit_fade: props.resitconfig && props.resitfade,
            resit_nofade: !props.resitconfig || !props.resitfade
        }
    ));

    /**
     * Get indent/padding class
     */
    const indentstyle = computed(() => {
        const padding = props.depth * 30;

        return {
            'padding-left': padding + 'px !important',
        }
    });

    /**
     * Save selected/deselected resit item
     */
    function save_resit_item(itemid: number, set: boolean) {
        moodleFetch(
            'local_gugrades_save_resit_item',
            {
                itemid: itemid,
                set: set,
            }
        )
        .catch((error) => {
            window.console.log(error);
            emit('saverror', error);
        });
    }

    /**
     * A resit box was clicked.
     */
    function resit_clicked(itemid: number) {
        if (resititemid.value == itemid) {
            resititemid.value = null;
            save_resit_item(itemid, false);
        } else {
            resititemid.value = itemid;
            save_resit_item(itemid, true);
        }
    }

    // As emit only works for one level, this re-emits events
    // from lower levels.
    function sub_activity_click(activityid: number) {
        emit('activityselected', activityid);
    }
</script>

<style>
    .resit_fade {
        opacity: 0.2;
    }

    .resit_nofade {
        opacity: 1.0;
    }

    td.resit_select {
        width: 1%;
        white-space: nowrap;
    }

    td {
        word-wrap:break-word;
    }
</style>