<template>
  <Popover class="relative">
    <PopoverButton ref="menuButton" class="cursor-pointer" aria-label="Open menu">
      <EllipsisVertical :size="18" class="text-base-content" tabindex="0" />
    </PopoverButton>

    <Portal>
    <PopoverPanel ref="menuPanel" :style="floatingStyles" class="border border-slate-200 rounded-lg shadow-lg z-999 overflow-hidden" v-slot="{ close }">
        <ul class="bg-white text-university-blue w-52 py-1 divide-y divide-slate-100" tabindex="-1">
            <li v-if="props.awaitingcapture && !props.converted && caneditgrades">
              <ImportUserGradeButton :itemid="props.itemid" :userid="props.userid" @imported="grade_added()" :close="close" />
            </li>
            <li v-if="caneditgrades">
              <AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.categoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" @gradeadded="grade_added()" :close="close" />
            </li>
            <li>
              <HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close" />
            </li>
            <li v-if="caneditgrades">
              <HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()" :close="close" />
            </li>
        </ul>
    </PopoverPanel>
    </Portal>
  </Popover>
</template>

<script setup lang="ts">
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import ImportUserGradeButton from '@/components/Capture/ImportUserGradeButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';
    import { ref } from 'vue';
    import { autoUpdate, flip, offset, shift, useFloating } from '@floating-ui/vue';
    import { Popover, PopoverButton, PopoverPanel, Portal } from '@headlessui/vue'
    import { EllipsisVertical } from '@lucide/vue';

    const menuButton = ref<InstanceType<typeof PopoverButton> | null>(null);
    const menuPanel = ref<InstanceType<typeof PopoverPanel> | null>(null);
    const { floatingStyles } = useFloating(menuButton, menuPanel, {
      placement: 'right-start',
      strategy: 'fixed',
      middleware: [offset(8), flip(), shift({ padding: 8 })],
      whileElementsMounted: autoUpdate,
    });

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