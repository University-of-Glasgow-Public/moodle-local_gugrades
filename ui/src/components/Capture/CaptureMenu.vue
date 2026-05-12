<template>
  <Popover class="relative">
    <PopoverButton aria-label="Open menu"><EllipsisVerticalIcon class="size-6 text-base_content" tabindex="0"></EllipsisVerticalIcon></PopoverButton>

    <PopoverPanel class="border-2 border-base-300 rounded-md absolute z-999 top-auto bottom-full mb-2 left-1/2 -translate-x-1/2" v-slot="{ close }">
        <ul class="menu dropdown-content bg-base-100 text-base-content rounded-box z-999 w-52 p-2 shadow-sm" tabindex="-1">
            <li v-if="props.awaitingcapture && !props.converted && caneditgrades"><ImportUserGradeButton :itemid="props.itemid" :userid="props.userid" @imported="grade_added()" :close="close"></ImportUserGradeButton></li>
            <li v-if="caneditgrades"><AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.categoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" @gradeadded = "grade_added()" :close="close"></AddGradeButton></li>
            <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
            <li v-if="caneditgrades" ><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()" :close="close"></HideShowButton></li>
        </ul>
    </PopoverPanel>
  </Popover>
</template>

<script setup lang="ts">
    import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import ImportUserGradeButton from '@/components/Capture/ImportUserGradeButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'

    const props = defineProps({
        userid: Number,
        item: Object,
        itemid: Number,
        categoryid: Number,
        itemname: String,
        name: String,
        awaitingcapture: Boolean,
        gradehidden: Boolean,
        converted: Boolean,
        caneditgrades: Boolean,
    });

    const emit = defineEmits([
        'gradeadded'
    ]);

    function grade_added() {
        emit('gradeadded');
    }

</script>

<style>
    .dropdown-menu.show {
        overflow: visible;
        z-index: 9999;
    }
</style>