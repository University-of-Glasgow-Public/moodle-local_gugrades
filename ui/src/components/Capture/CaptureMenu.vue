<template>
  <Popover class="relative">
    <PopoverButton class="cursor-pointer" aria-label="Open menu"><EllipsisVertical :size="18" class="text-base_content" tabindex="0"></EllipsisVertical></PopoverButton>

    <PopoverPanel class="border-2 border-slate-200 rounded-md absolute z-999 left-0 top-0 ml-6 -mt-12" v-slot="{ close }">
        <ul class="bg-slate-100 text-university-blue rounded-lg z-999 w-52 p-2 shadow-sm flex flex-col gap-0.5" tabindex="-1">
            <li v-if="props.awaitingcapture && !props.converted && caneditgrades"><ImportUserGradeButton :itemid="props.itemid" :userid="props.userid" @imported="grade_added()" :close="close"></ImportUserGradeButton></li>
            <li v-if="caneditgrades"><AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.categoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" @gradeadded = "grade_added()" :close="close"></AddGradeButton></li>
            <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
            <li v-if="caneditgrades" ><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()" :close="close"></HideShowButton></li>
        </ul>
    </PopoverPanel>
  </Popover>
</template>

<script setup lang="ts">
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import ImportUserGradeButton from '@/components/Capture/ImportUserGradeButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { EllipsisVertical } from '@lucide/vue';

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