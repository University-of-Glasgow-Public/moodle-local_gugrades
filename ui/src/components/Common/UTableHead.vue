<template>
  <thead class="bg-university-blue text-white uppercase text-xs tracking-wider" :class="{ 'utable-grade-head': bottomGradeLabels }">
    <template v-for="(headerGroup, index) in headerRows" :key="headerGroup.id">
    <tr v-if="!headerGroup.filter">
      <th
        v-for="header in headerGroup.headers"
        :key="header.id"
        :colspan="header.colSpan"
        :scope="header.column.columns.length ? 'colgroup' : 'col'"
        class="font-semibold p-0"
        :class="[
          dense ? 'text-xs' : '',
          isPinnedColumn(header.column.id) ? 'utable-pin-name' : '',
          isScrolledX && isPinnedColumn(header.column.id) ? 'utable-pin-name--scrolled' : '',
          header.column.columns.length ? (bottomGradeLabels ? 'border-r border-white/30 text-center' : 'border-x border-white/30 bg-white/10 text-center') : '',
          header.column.parent ? (bottomGradeLabels ? 'normal-case tracking-normal' : 'border-x border-white/20 normal-case tracking-normal') : '',
          bottomGradeLabels ? 'border-r border-white/30' : '',
          bottomGradeLabels && header.column.parent?.columns.length === 2 ? 'utable-dual-grade' : '',
        ]"
      >
        <div
          v-if="bottomGradeLabels ? (index === 0 || !!header.column.parent) : !header.isPlaceholder"
          class="flex gap-1.5 w-full transition-colors duration-150"
          :class="[
            bottomGradeLabels ? 'px-2 py-1 gap-1' : dense ? 'px-3 py-1.5' : 'px-6 py-2.5',
            bottomGradeLabels && index === 0 && !header.column.columns.length && header.column.id !== 'back' ? 'h-9 items-center text-sm font-bold leading-tight' : 'items-stretch',
            header.column.columns.length ? 'justify-center' : '',
            bottomGradeLabels && header.column.parent ? 'justify-center whitespace-nowrap' : '',
            header.column.getCanSort() ? 'cursor-pointer hover:bg-white/10' : ''
          ]"
          @click="header.column.getToggleSortingHandler()?.($event)"
        >
          <FlexRender
            :render="header.column.columnDef.header"
            :props="header.getContext()"
          />
          <component
            v-if="bottomGradeLabels && header.column.getCanSort()"
            :is="header.column.getIsSorted() === 'asc' ? ArrowUp : header.column.getIsSorted() === 'desc' ? ArrowDown : ArrowUpDown"
            :size="11"
            class="shrink-0 self-center"
            :class="header.column.getIsSorted() ? '' : 'opacity-40'"
            aria-hidden="true"
          />
          <span v-else-if="header.column.getIsSorted() === 'asc'">🔼</span>
          <span v-else-if="header.column.getIsSorted() === 'desc'">🔽</span>
          <span v-else-if="header.column.getCanSort()" class="opacity-30">↕️</span>
        </div>
      </th>
    </tr>

      <tr v-else :class="bottomGradeLabels ? '' : 'bg-university-blue/90'">
        <th
          v-for="column in table.getVisibleLeafColumns()"
          :key="`filter-${column.id}`"
          class="font-normal p-1"
          :class="[
            isPinnedColumn(column.id) ? 'utable-pin-name utable-pin-name--filter' : '',
            isScrolledX && isPinnedColumn(column.id) ? 'utable-pin-name--scrolled' : '',
            column.parent ? (bottomGradeLabels ? 'utable-grade-filter' : 'border-x border-white/20') : '',
            bottomGradeLabels ? 'border-r border-white/30' : '',
            bottomGradeLabels && column.parent?.columns.length === 2 ? 'utable-dual-grade' : '',
          ]"
        >
          <input
            v-if="column.getCanFilter()"
            type="text"
            :value="(column.getFilterValue() as string) ?? ''"
            @input="column.setFilterValue(($event.target as HTMLInputElement).value)"
            placeholder="Filter..."
            class="w-full rounded-sm border border-white/30 bg-white/10 text-white placeholder-white/50 text-xs px-2 py-1 focus:outline-none focus:border-white/60"
            @click.stop
          />
        </th>
      </tr>
    </template>
  </thead>
</template>

<script setup lang="ts">
    import { computed } from 'vue'
    import { FlexRender, type Table } from '@tanstack/vue-table'
    import { ArrowUp, ArrowDown, ArrowUpDown } from '@lucide/vue'

    const props = defineProps<{
        table: Table<any>;
        dense?: boolean;
        filterable?: boolean;
        isScrolledX?: boolean;
        bottomGradeLabels?: boolean;
    }>();

    const headerRows = computed(() => {
        const groups = props.table.getHeaderGroups().map(group => ({ ...group, filter: false }));
        if (props.filterable) {
            const filterRow = { id: 'filters', headers: [], depth: 0, filter: true };
            groups.splice(props.bottomGradeLabels ? Math.max(0, groups.length - 1) : groups.length, 0, filterRow);
        }
        return groups;
    });

    function isPinnedColumn(columnId: string) {
        return columnId === 'displayname';
    }

</script>

<style scoped>
  .utable-grade-head {
    text-transform: none;
    letter-spacing: normal;
    font-size: 11px;
  }

  .utable-grade-head th {
    vertical-align: top;
  }

  .utable-dual-grade {
    width: 1%;
    white-space: nowrap;
  }

  .utable-grade-filter input {
    width: 0;
    min-width: 100%;
  }

  .utable-grade-head input {
    padding: 2px 4px;
    font-size: 11px;
  }

  .utable-grade-head .utable-pin-name--filter {
    background-color: var(--color-university-blue) !important;
  }

  .utable-pin-name {
    position: sticky;
    left: 0;
    z-index: 4;
    background-color: var(--color-university-blue) !important;
  }

  .utable-pin-name--filter {
    background-color: color-mix(in srgb, var(--color-university-blue) 90%, black) !important;
  }

  .utable-pin-name--scrolled {
    box-shadow: 6px 0 8px -6px rgba(1, 20, 81, 0.35);
  }
</style>
