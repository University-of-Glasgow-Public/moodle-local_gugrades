<template>
  <div class="w-full flex flex-col gap-4">
    <!-- Main Scrollable Table Containment Frame -->
    <div class="w-full overflow-x-auto rounded-lg border border-brand-light-purple/30 shadow-md">
      <table class="w-full text-left border-collapse bg-white text-sm">
        
        <!-- HEADER ROW -->
        <thead class="bg-university-blue text-white uppercase text-xs tracking-wider">
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th 
              v-for="header in headerGroup.headers" 
              :key="header.id" 
              class="font-semibold transition-all duration-150"
              :class="dense ? 'px-3 py-2 text-xs' : 'px-6 py-4'"
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
            class="odd:bg-brand-light-purple/5 hover:bg-brand-light-purple/15 hover:text-university-blue transition-colors duration-150 ease-in-out"
          >
            <td 
              v-for="cell in row.getVisibleCells()" 
              :key="cell.id" 
              class="whitespace-nowrap transition-all duration-150"
              :class="dense ? 'px-3 py-1.5 text-xs' : 'px-6 py-4'"
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

    <!-- Branded Pagination Controls Footer -->
    <div 
      class="flex items-center justify-between text-sm text-brand-dark-purple font-medium"
      :class="dense ? 'px-1 gap-2 text-xs' : 'px-2'"
    >
      
      <!-- Left Side Data Summary Status Numbers -->
      <div class="flex items-center gap-1 select-none text-brand-dark-purple/70">
        <span>Showing page</span>
        <strong class="text-university-blue font-bold">
          {{ table.getState().pagination.pageIndex + 1 }}
        </strong>
        <span>of</span>
        <strong class="text-university-blue font-bold">
          {{ table.getPageCount() }}
        </strong>
      </div>

      <!-- Right Side Navigation Control Buttons Row -->
      <div class="flex items-center gap-1.5">
        <button 
          class="border border-brand-light-purple/30 rounded-lg bg-white font-bold text-university-blue shadow-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-brand-light-purple/10 transition-colors cursor-pointer"
          :class="dense ? 'px-2 py-1 text-xs' : 'px-3 py-1.5'"
          :disabled="!table.getCanPreviousPage()"
          @click="table.setPageIndex(0)"
          aria-label="First page"
        >
          «
        </button>

        <button 
          class="border border-brand-light-purple/30 rounded-lg bg-white font-semibold text-university-blue shadow-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-brand-light-purple/10 transition-colors cursor-pointer"
          :class="dense ? 'px-2 py-1 text-[11px]' : 'px-3 py-1.5 text-xs'"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
        >
          ‹ Previous
        </button>

        <button 
          class="border border-brand-light-purple/30 rounded-lg bg-white font-semibold text-university-blue shadow-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-brand-light-purple/10 transition-colors cursor-pointer"
          :class="dense ? 'px-2 py-1 text-[11px]' : 'px-3 py-1.5 text-xs'"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
        >
          Next ›
        </button>

        <button 
          class="border border-brand-light-purple/30 rounded-lg bg-white font-bold text-university-blue shadow-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-brand-light-purple/10 transition-colors cursor-pointer"
          :class="dense ? 'px-2 py-1 text-xs' : 'px-3 py-1.5'"
          :disabled="!table.getCanNextPage()"
          @click="table.setPageIndex(table.getPageCount() - 1)"
          aria-label="Last page"
        >
          »
        </button>

        <!-- Rows Per Page Menu Control Dropdown Selector -->
        <select 
          class="ml-2 bg-white text-brand-dark-purple border border-brand-light-purple/30 rounded-lg shadow-sm focus:outline-none focus:border-university-blue font-semibold cursor-pointer"
          :class="dense ? 'px-1.5 py-1 text-[11px]' : 'px-2.5 py-1.5 text-xs'"
          :value="table.getState().pagination.pageSize"
          @change="e => table.setPageSize(Number((e.target as HTMLSelectElement).value))"
          aria-label="Select row page size count"
        >
          <!-- FIXED: Completely qualified loop array syntax -->
          <option v-for="size in [25, 50, 100, 250]" :key="size" :value="size">
            Show {{ size }}
          </option>
        </select>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts" generic="TData">
    import { 
        useVueTable, 
        getCoreRowModel, 
        getPaginationRowModel,
        FlexRender,
        type ColumnDef 
    } from '@tanstack/vue-table'

    interface BaseTableProps {
        data: TData[];
        columns: ColumnDef<TData, any>[];
        dense?: boolean;
    }

    const props = withDefaults(defineProps<BaseTableProps>(), {
        dense: false
    })

    const table = useVueTable({
        get data() { return props.data },
        get columns() { return props.columns },
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        initialState: {
            pagination: {
                pageSize: 25,
            },
        },
    });
</script>
