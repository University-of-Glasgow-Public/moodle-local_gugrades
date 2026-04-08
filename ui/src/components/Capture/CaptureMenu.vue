<template>
    <details class="tw:dropdown tw:dropdown-end tw:dropdown-left tw:dropdown-hover" tabindex="0">
        <summary class="tw:list-none"><EllipsisVerticalIcon class="tw:size-6 tw:text-black-500" tabindex="0"></EllipsisVerticalIcon></summary>
        <ul class="tw:menu tw:dropdown-content tw:bg-base-100 tw:rounded-box tw:z-999 tw:w-52 tw:p-2 tw:shadow-sm" tabindex="-1">
            <li @click="handleItemClick" v-if="props.awaitingcapture && !props.converted && caneditgrades"><ImportUserGradeButton :itemid="props.itemid" :userid="props.userid" @imported="grade_added()"></ImportUserGradeButton></li>
            <li @click="handleItemClick" v-if="caneditgrades"><AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.categoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" @gradeadded = "grade_added()"></AddGradeButton></li>
            <li @click="handleItemClick"><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname"></HistoryButton></li>
            <li @click="handleItemClick" v-if="caneditgrades" ><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()"></HideShowButton></li>
        </ul>
    </details>
</template>

<script setup lang="ts">
    import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import ImportUserGradeButton from '@/components/Capture/ImportUserGradeButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';

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