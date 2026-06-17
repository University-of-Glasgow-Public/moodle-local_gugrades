<template>
  <div class="w-full overflow-x-auto rounded-lg border border-brand-light-purple/30 shadow-md">
    <table class="w-full text-left border-collapse bg-white text-sm">
      
      <!-- HEADER ROW -->
      <thead class="bg-university-blue text-white uppercase text-xs tracking-wider">
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <th 
            v-for="header in headerGroup.headers" 
            :key="header.id" 
            class="px-6 py-4 font-semibold"
          >
            <FlexRender 
              :render="header.column.columnDef.header" 
              :props="header.getContext()" 
            />
          </th>
        </tr>
      </thead>

      <!-- BODY ROWS -->
      <tbody class="divide-y divide-brand-light-purple/20 text-brand-dark-purple">
        <tr 
          v-for="row in table.getRowModel().rows" 
          :key="row.id"
          class="hover:bg-brand-light-purple/10 transition-colors"
        >
          <td 
            v-for="cell in row.getVisibleCells()" 
            :key="cell.id" 
            class="px-6 py-4 whitespace-nowrap"
          >
            <FlexRender 
              :render="cell.column.columnDef.cell" 
              :props="cell.getContext()" 
            />
          </td>
        </tr>
      </tbody>

    </table>
  </div>
</template>


<!-- BaseTable.vue -->
<script setup lang="ts" generic="TData">
    import { 
        useVueTable, 
        getCoreRowModel, 
        FlexRender,
        type ColumnDef 
    } from '@tanstack/vue-table'

    // Define explicit TypeScript interfaces for your component properties
    interface BaseTableProps {
        data: TData[];
        columns: ColumnDef<TData, any>[];
    }

    const props = defineProps<BaseTableProps>()

    // Initialize the TanStack engine using the typed inputs
    const table = useVueTable({
        get data() { return props.data },
        get columns() { return props.columns },
        getCoreRowModel: getCoreRowModel(),
    });
</script>

