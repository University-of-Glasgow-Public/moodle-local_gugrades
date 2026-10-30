<template>
    <Popover class="relative">
        <PopoverButton ref="menuButton" class="cursor-pointer" aria-label="Open menu"><EllipsisVertical :size="18" class="text-base-content" tabindex="0"></EllipsisVertical></PopoverButton>

        <Portal>
        <PopoverPanel ref="menuPanel" :style="floatingStyles" class="border-2 border-base-300 rounded-md z-999" v-slot="{ close }">
            <ul v-if="props.categoryid == 0" class="flex flex-col bg-base-100 text-base-content rounded-md z-999 w-52 p-2 shadow-sm" tabindex="-1">
                <li v-if="caneditgrades" >
                    <AddGradeButton :itemid="props.itemid" :selectedcategoryid="props.selectedcategoryid" :userid="props.userid" :name="props.name" :itemname="props.itemname" :released="props.released" @gradeadded="grade_added" :close="close"></AddGradeButton>
                </li>
                <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
                <li v-if="caneditgrades"><HideShowButton :gradehidden="props.gradehidden" :itemid="props.itemid" :userid="props.userid" @changed="grade_added()" :close="close"></HideShowButton></li>
            </ul>
            <ul v-else class="flex flex-col bg-base-100 text-base-content rounded-md z-999 w-52 p-2 shadow-sm" tabindex="-1">
                <li v-if="caneditgrades"><AddGradeButton
                    :itemid="props.itemid"
                    :selectedcategoryid="props.selectedcategoryid"
                    :categoryid="props.categoryid"
                    :userid="props.userid"
                    :name="props.name"
                    :itemname="props.itemname"
                    :released="props.released"
                    :close="close"
                    @gradeadded="grade_added">
                </AddGradeButton></li>
                <li><HistoryButton :userid="props.userid" :itemid="props.itemid" :name="props.name" :itemname="props.itemname" :close="close"></HistoryButton></li>
                <li v-if="props.showweights && !props.overridden && caneditgrades"><AlterButton :userid="props.userid" :itemid="props.itemid" :categoryid="props.categoryid" @weightsaltered="grade_added()" :close="close"></AlterButton></li>
                <li><ExplainButton :userid="props.userid" :categoryid="props.categoryid" :close="close"></ExplainButton></li>
            </ul>
        </PopoverPanel>
        </Portal>
    </Popover>
</template>

<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { autoUpdate, flip, offset, shift, useFloating } from '@floating-ui/vue';
    import HistoryButton from '@/components/Capture/HistoryButton.vue';
    import AddGradeButton from '@/components/Capture/AddGradeButton.vue';
    import HideShowButton from '@/components/Capture/HideShowButton.vue';
    import AlterButton from '@/components/Aggregation/AlterButton.vue';
    import ExplainButton from '@/components/Aggregation/ExplainButton.vue';
    import { EllipsisVertical } from '@lucide/vue';
    import { Popover, PopoverButton, PopoverPanel, Portal } from '@headlessui/vue';

    interface IProps {
        itemid: number;
        categoryid: number;
        selectedcategoryid: number;
        userid: number;
        gradehidden: boolean;
        overridden: boolean;
        itemname: string;
        name: string;
        showweights: boolean;
        released: boolean;
        caneditgrades: boolean;
        position: 'above' | 'below';
    }

    const props = defineProps< IProps >();

    const menuButton = ref<InstanceType<typeof PopoverButton> | null>(null);
    const menuPanel = ref<InstanceType<typeof PopoverPanel> | null>(null);
    const { floatingStyles } = useFloating(menuButton, menuPanel, {
        placement: computed(() => props.position === 'below' ? 'bottom' : 'top'),
        strategy: 'fixed',
        middleware: [offset(8), flip(), shift({ padding: 8 })],
        whileElementsMounted: autoUpdate,
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