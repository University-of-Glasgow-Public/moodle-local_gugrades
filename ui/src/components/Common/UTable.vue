<template>
  <div class="w-full flex flex-col gap-4">
    <!-- Main Scrollable Table Containment Frame -->
    <div class="w-full overflow-x-auto rounded-lg border border-brand-light-purple/30 bg-white shadow-md">
      
      <!-- GRID CONTAINER: Replaces <table> with a clean, flexible block structure -->
      <div class="w-full min-w-max text-sm text-left">
        
        <!-- HEADER ROW GROUP: Replaces <thead> -->
        <div class="w-full bg-university-blue text-white uppercase text-xs tracking-wider font-semibold flex">
          <div v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="w-full flex">
            
            <!-- Individual Header Cells -->
            <div 
              v-for="header in headerGroup.headers" 
              :key="header.id" 
              class="flex-1 transition-all duration-150"
              :class="dense ? 'px-3 py-1.5 text-xs' : 'px-6 py-2.5'"
            >
              <FlexRender 
                :render="header.column.columnDef.header" 
                :props="header.getContext()" 
              />
            </div>

          </div>
        </div>

        <!-- BODY ROWS GROUP: Replaces <tbody> -->
        <div class="w-full divide-y divide-brand-light-purple/20 text-brand-dark-purple flex flex-col">
          
          <!-- 
            THE DEFINITIVE FIX: Replaces <tr> with an absolute Block Div.
            - 'odd:bg-brand-light-purple/5' handles zebra striping natively on the block.
            - 'hover:bg-brand-light-purple/15' handles row hovers safely.
          -->
          <div 
            v-for="row in table.getRowModel().rows" 
            :key="row.id"
            class="w-full flex items-center bg-white odd:bg-brand-light-purple/5 hover:bg-brand-light-purple/15 hover:text-university-blue transition-all duration-150 ease-in-out"
          >
            
            <!-- Individual Body Data Cells (Replaces <td>) -->
            <div 
              v-for="cell in row.getVisibleCells()" 
              :key="cell.id" 
              class="flex-1 whitespace-normal transition-all duration-150"
              :class="dense ? 'px-3 py-1 text-xs' : 'px-6 py-2'"
            >
              <FlexRender 
                :render="cell.column.columnDef.cell" 
                :props="cell.getContext()" 
              />
            </div>

          </div>

        </div>

      </div>
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
          <option v-for="size in [25, 50, 100, 250, 500]" :key="size" :value="size">
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

<style scoped>
  /* 
    1. TARGET THE ALTERNATING STRIPES:
    We mix 5% of your brand light purple hex color string with transparency.
  */
  tbody tr:nth-child(odd) {
    background-color: color-mix(in srgb, var(--color-brand-light-purple) 5%, transparent) !important;
  }

  /* 
    2. TARGET THE ROW HOVER HIGHLIGHT:
    We mix 15% of your brand light purple hex color string with transparency.
  */
  tbody tr:hover {
    background-color: color-mix(in srgb, var(--color-brand-light-purple) 15%, transparent) !important;
    color: var(--color-university-blue) !important;
  }

  /* 
    3. CLEAR BROWSER DEFAULT CELL MASKS:
    Forces individual cells to be transparent so the row backgrounds bleed through.
  */
  tbody td {
    background-color: transparent !important;
  }
</style>
