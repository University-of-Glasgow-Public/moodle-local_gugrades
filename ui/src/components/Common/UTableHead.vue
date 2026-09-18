<template>
  <thead class="bg-university-blue text-white uppercase text-xs tracking-wider">
    <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
      <th
        v-for="header in headerGroup.headers"
        :key="header.id"
        class="font-semibold p-0"
        :class="[
          dense ? 'text-xs' : '',
          isPinnedColumn(header.column.id) ? 'utable-pin-name' : '',
          isScrolledX && isPinnedColumn(header.column.id) ? 'utable-pin-name--scrolled' : '',
        ]"
      >
        <div
          class="flex items-center gap-1.5 w-full items-stretch transition-colors duration-150"
          :class="[
            dense ? 'px-3 py-1.5' : 'px-6 py-2.5',
            header.column.getCanSort() ? 'cursor-pointer hover:bg-white/10' : ''
          ]"
          @click="header.column.getToggleSortingHandler()?.($event)"
        >
          <FlexRender
            :render="header.column.columnDef.header"
            :props="header.getContext()"
          />
          <span v-if="header.column.getIsSorted() === 'asc'">🔼</span>
          <span v-else-if="header.column.getIsSorted() === 'desc'">🔽</span>
          <span v-else-if="header.column.getCanSort()" class="opacity-30">↕️</span>
        </div>
      </th>
    </tr>

    <template v-if="filterable">
      <tr v-for="headerGroup in table.getHeaderGroups()" :key="`filter-${headerGroup.id}`" class="bg-university-blue/90">
        <th
          v-for="header in headerGroup.headers"
          :key="`filter-${header.id}`"
          class="font-normal p-1"
          :class="[
            isPinnedColumn(header.column.id) ? 'utable-pin-name utable-pin-name--filter' : '',
            isScrolledX && isPinnedColumn(header.column.id) ? 'utable-pin-name--scrolled' : '',
          ]"
        >
          <input
            v-if="header.column.getCanFilter()"
            type="text"
            :value="(header.column.getFilterValue() as string) ?? ''"
            @input="header.column.setFilterValue(($event.target as HTMLInputElement).value)"
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
    import { FlexRender, type Table } from '@tanstack/vue-table'

    defineProps<{
        table: Table<any>;
        dense?: boolean;
        filterable?: boolean;
        isScrolledX?: boolean;
    }>();

    function isPinnedColumn(columnId: string) {
        return columnId === 'displayname';
    }
</script>

<style scoped>
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
