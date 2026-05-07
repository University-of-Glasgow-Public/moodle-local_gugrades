<template>
    <!--
    <details class="dropdown dropdown-end dropdown-left dropdown-hover" tabindex="0">
        <summary class="list-none"><EllipsisVerticalIcon class="size-6 text-black-500" tabindex="0"></EllipsisVerticalIcon></summary>
        <ul v-if="props.categoryid == 0" class="menu dropdown-content bg-base-100 rounded-box z-999 w-52 p-2 shadow-sm" tabindex="-1">
            <li @click="handleItemClick" v-if="caneditgrades" >
                <AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.selectedcategoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" :released="props.released" @gradeadded = "grade_added()"></AddGradeButton>
            </li>
            <li @click="handleItemClick"><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname"></HistoryButton></li>
            <li @click="handleItemClick" v-if="caneditgrades"><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()"></HideShowButton></li>
        </ul>
        <ul v-else class="menu dropdown-content bg-base-100 rounded-box z-999 w-52 p-2 shadow-sm" tabindex="-1">
            <li @click="handleItemClick" v-if="caneditgrades"><AddGradeButton
                :itemid="props.itemid"
                :selectedcategoryid="props.selectedcategoryid"
                :categoryid="props.categoryid"
                :userid="props.userid"
                :name="props.name"
                :itemname="props.itemname"
                :released="props.released"
                @gradeadded = "grade_added()">
            </AddGradeButton></li>
            <li @click="handleItemClick"><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname"></HistoryButton></li>
            <li @click="handleItemClick" v-if="props.showweights && !props.overridden && caneditgrades"><AlterButton :userid="props.userid" :itemid="props.itemid" :categoryid="props.categoryid" @weightsaltered="grade_added()"></AlterButton></li>
            <li @click="handleItemClick"><ExplainButton :userid="props.userid" :categoryid="props.categoryid"></ExplainButton></li>
        </ul>
    </details>
-->

    <Popover class="relative">
        <PopoverButton><EllipsisVerticalIcon class="size-6 text-black-500" tabindex="0"></EllipsisVerticalIcon></PopoverButton>

        <PopoverPanel class="border-2 border-gray-300 rounded-md absolute z-999 top-auto bottom-full mb-2 left-1/2 -translate-x-1/2" v-slot="{ close }">
            <ul v-if="props.categoryid == 0" class="menu dropdown-content bg-base-100 rounded-box z-999 w-52 p-2 shadow-sm" tabindex="-1">
                <li v-if="caneditgrades" >
                    <AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.selectedcategoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" :released="props.released" @gradeadded = "grade_added()" :close="close"></AddGradeButton>
                </li>
                <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
                <li v-if="caneditgrades"><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()" :close="close"></HideShowButton></li>
            </ul>
            <ul v-else class="menu dropdown-content bg-base-100 rounded-box z-999 w-52 p-2 shadow-sm" tabindex="-1">
                <li v-if="caneditgrades"><AddGradeButton
                    :itemid="props.itemid"
                    :selectedcategoryid="props.selectedcategoryid"
                    :categoryid="props.categoryid"
                    :userid="props.userid"
                    :name="props.name"
                    :itemname="props.itemname"
                    :released="props.released"
                    :close="close"
                    @gradeadded = "grade_added()">
                </AddGradeButton></li>
                <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
                <li v-if="props.showweights && !props.overridden && caneditgrades"><AlterButton :userid="props.userid" :itemid="props.itemid" :categoryid="props.categoryid" @weightsaltered="grade_added()" :close="close"></AlterButton></li>
                <li><ExplainButton :userid="props.userid" :categoryid="props.categoryid" :close="close"></ExplainButton></li>
            </ul>
        </PopoverPanel>
    </Popover>
</template>

<script setup lang="ts">
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';
    import AlterButton from '@/components/Aggregation/AlterButton.vue';
    import ExplainButton from '@/components/Aggregation/ExplainButton.vue';
    import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';

    const props = defineProps({
        userid: Number,
        item: Object,
        itemid: Number,
        categoryid: Number,
        selectedcategoryid: Number,
        itemname: String,
        name: String,
        awaitingcapture: Boolean,
        gradehidden: Boolean,
        converted: Boolean,
        overridden: Boolean,
        showweights: Boolean,
        released: Boolean,
        caneditgrades: Boolean,
    });

    const emit = defineEmits([
        'gradeadded'
    ]);

    function grade_added() {
        emit('gradeadded');
    }

    const handleItemClick = (event: MouseEvent) => {
        // Close the dropdown by removing the 'open' attribute
        const target = event.target as HTMLElement;
        const dropdown = target.closest('details');
        if (dropdown) {
            dropdown.removeAttribute('open');
        }
    };

</script>

<style>
    .dropdown-menu.show {
        overflow: visible;
        z-index: 9999;
    }
</style>