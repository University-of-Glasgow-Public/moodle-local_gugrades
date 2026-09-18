<template>
  <div class="w-full flex flex-col gap-4">
    <!-- Main Scrollable Table Containment Frame -->
    <div class="w-full rounded-lg border border-brand-light-purple/30 bg-white shadow-md">
      <div
        :id="tableScrollId"
        ref="tableScrollEl"
        class="utable-scroll w-full overflow-x-auto overflow-y-visible"
        @scroll="onTableScroll"
      >
        <table ref="tableEl" class="w-full text-left border-collapse text-sm">
        
        <!-- HEADER ROW GROUP -->
        <UTableHead
          :table="table"
          :dense="dense"
          :filterable="filterable"
          :is-scrolled-x="isScrolledX"
        />

        <!-- BODY ROWS GROUP -->
        <tbody class="divide-y divide-brand-light-purple/20 text-brand-dark-purple">
          <tr 
            v-for="row in table.getRowModel().rows" 
            :key="row.id"
            class="transition-colors duration-150 ease-in-out hover:text-university-blue"
          >
            <td 
              v-for="cell in row.getVisibleCells()" 
              :key="cell.id" 
              class="transition-all duration-150"
              :class="[
                dense ? 'px-3 py-1 text-xs' : 'px-6 py-2',
                isPinnedColumn(cell.column.id) ? 'utable-pin-name' : '',
                isScrolledX && isPinnedColumn(cell.column.id) ? 'utable-pin-name--scrolled' : '',
              ]"
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
    </div>

    <!-- Follows the page as the original header leaves the viewport. -->
    <Teleport to="body">
      <div
        v-show="showStickyHeader"
        ref="stickyHeaderEl"
        class="utable-sticky-header"
        :style="stickyHeaderStyle"
      >
        <div ref="stickyHeaderScrollEl" class="utable-sticky-header-scroll">
          <table class="text-left border-collapse text-sm" :style="{ width: contentWidth + 'px' }">
            <UTableHead
              :table="table"
              :dense="dense"
              :filterable="filterable"
              :is-scrolled-x="isScrolledX"
            />
          </table>
        </div>
      </div>
    </Teleport>

    <!-- Stays at the bottom of the viewport while the native table scrollbar
         is off-screen, so wide tables can be panned without scrolling down. -->
    <Teleport to="body">
      <div
        v-show="showFloatingScrollbar"
        ref="floatingScrollEl"
        class="utable-floating-scroll"
        :style="floatingScrollStyle"
        role="scrollbar"
        aria-orientation="horizontal"
        aria-label="Scroll table horizontally"
        :aria-controls="tableScrollId"
        :aria-valuemin="0"
        :aria-valuenow="scrollLeft"
        :aria-valuemax="maxScrollLeft"
        @scroll="onFloatingScroll"
      >
        <div class="utable-floating-scroll-spacer" :style="{ width: contentWidth + 'px' }"></div>
      </div>
    </Teleport>

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
    import { computed, nextTick, onMounted, ref, useId, watch, type CSSProperties } from 'vue'
    import UTableHead from '@/components/Common/UTableHead.vue'
    import { useEventListener, useResizeObserver } from '@vueuse/core'
    import { 
        useVueTable, 
        getCoreRowModel, 
        getPaginationRowModel,
        FlexRender,
        getFilteredRowModel,
        getSortedRowModel,
        type ColumnDef,
        type ColumnFiltersState, 
        type VisibilityState,
        type SortingState
    } from '@tanstack/vue-table'

    interface BaseTableProps {
        data: TData[];
        columns: ColumnDef<TData, any>[];
        dense?: boolean;
        filters?: ColumnFiltersState;
        visibility?: VisibilityState;
        initialSort?: SortingState;
        sortable?: boolean;
        filterable?: boolean;
    }

    const props = withDefaults(defineProps<BaseTableProps>(), {
        dense: false,
        filters: () => [],
        visibility: () => ({}),
        initialSort: () => [],
        sortable: true,
        filterable: true,
    });

    // This allows users to click and change sorting later if needed
    const sorting = ref<SortingState>(props.initialSort);
    const columnFilters = ref<ColumnFiltersState>(props.filters);

    const table = useVueTable({
        get data() { return props.data },
        get columns() { return props.columns },
        
        // 2. ADD THE ACTIVE STATE GETTERS
        state: {
            get columnFilters() { return columnFilters.value },
            get columnVisibility() { return props.visibility },
            get sorting() { return sorting.value }
        },

        onSortingChange: (updater) => {
            sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
        },
        onColumnFiltersChange: (updater) => {
            columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater
        },

        enableSorting: props.sortable,
        
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getFilteredRowModel: getFilteredRowModel(), 
        getSortedRowModel: getSortedRowModel(),
        
        autoResetAll: false,
        initialState: {
            pagination: {
                pageSize: 25,
            },
        },
    });

    const tableScrollId = useId();
    const tableScrollEl = ref<HTMLElement | null>(null);
    const tableEl = ref<HTMLElement | null>(null);
    const floatingScrollEl = ref<HTMLElement | null>(null);
    const stickyHeaderEl = ref<HTMLElement | null>(null);
    const stickyHeaderScrollEl = ref<HTMLElement | null>(null);
    const contentWidth = ref(0);
    const scrollLeft = ref(0);
    const isScrolledX = ref(false);
    const showFloatingScrollbar = ref(false);
    const showStickyHeader = ref(false);
    const floatingScrollStyle = ref<CSSProperties>({});
    const stickyHeaderStyle = ref<CSSProperties>({});
    let syncingScroll = false;
    let measureRaf = 0;

    const maxScrollLeft = computed(() => {
        const clientWidth = tableScrollEl.value?.clientWidth ?? 0;
        return Math.max(contentWidth.value - clientWidth, 0);
    });

    function isPinnedColumn(columnId: string) {
        return columnId === 'displayname';
    }

    function onTableScroll() {
        const el = tableScrollEl.value;
        if (!el) {
            return;
        }

        scrollLeft.value = el.scrollLeft;
        isScrolledX.value = el.scrollLeft > 0;

        if (stickyHeaderScrollEl.value) {
            stickyHeaderScrollEl.value.scrollLeft = el.scrollLeft;
        }

        if (syncingScroll || !floatingScrollEl.value) {
            return;
        }

        syncingScroll = true;
        floatingScrollEl.value.scrollLeft = el.scrollLeft;
        if (stickyHeaderScrollEl.value) {
            stickyHeaderScrollEl.value.scrollLeft = el.scrollLeft;
        }
        requestAnimationFrame(() => {
            syncingScroll = false;
        });
    }

    function onFloatingScroll() {
        const el = tableScrollEl.value;
        const floating = floatingScrollEl.value;
        if (!el || !floating || syncingScroll) {
            return;
        }

        syncingScroll = true;
        el.scrollLeft = floating.scrollLeft;
        scrollLeft.value = el.scrollLeft;
        isScrolledX.value = el.scrollLeft > 0;
        if (stickyHeaderScrollEl.value) {
            stickyHeaderScrollEl.value.scrollLeft = el.scrollLeft;
        }
        requestAnimationFrame(() => {
            syncingScroll = false;
        });
    }

    function getStickyTopOffset() {
        const candidates = document.querySelectorAll('.navbar, header.navbar, .fixed-top, #page-header');
        let offset = 0;

        candidates.forEach((node) => {
            const style = window.getComputedStyle(node);
            if (style.position !== 'fixed' && style.position !== 'sticky') {
                return;
            }

            const rect = node.getBoundingClientRect();
            if (rect.height > 0 && rect.top <= 8 && rect.bottom > offset && rect.bottom < 200) {
                offset = rect.bottom;
            }
        });

        return offset;
    }

    function syncStickyHeaderWidths() {
        const sourceHead = tableEl.value?.querySelector('thead');
        const destHead = stickyHeaderScrollEl.value?.querySelector('thead');
        if (!sourceHead || !destHead) {
            return;
        }

        const sourceRows = sourceHead.querySelectorAll('tr');
        const destRows = destHead.querySelectorAll('tr');

        sourceRows.forEach((row: Element, rowIndex: number) => {
            const destRow = destRows[rowIndex];
            if (!destRow) {
                return;
            }

            Array.from(row.children).forEach((cell, cellIndex) => {
                const destCell = destRow.children[cellIndex] as HTMLElement | undefined;
                if (!destCell) {
                    return;
                }

                const width = (cell as HTMLElement).getBoundingClientRect().width;
                destCell.style.width = `${width}px`;
                destCell.style.minWidth = `${width}px`;
                destCell.style.maxWidth = `${width}px`;
            });
        });
    }

    function updateStickyHeader() {
        const el = tableScrollEl.value;
        const tableNode = tableEl.value;
        if (props.dense || !el || !tableNode) {
            showStickyHeader.value = false;
            return;
        }

        const thead = tableNode.querySelector('thead');
        if (!thead) {
            showStickyHeader.value = false;
            return;
        }

        const tableRect = el.getBoundingClientRect();
        const theadRect = thead.getBoundingClientRect();
        const topOffset = getStickyTopOffset();
        const stillOverTable = tableRect.bottom > topOffset + 48;

        showStickyHeader.value = theadRect.top < topOffset && stillOverTable;
        stickyHeaderStyle.value = {
            top: `${topOffset}px`,
            left: `${tableRect.left}px`,
            width: `${tableRect.width}px`,
        };

        nextTick(() => {
            syncStickyHeaderWidths();
            if (stickyHeaderScrollEl.value && tableScrollEl.value) {
                stickyHeaderScrollEl.value.scrollLeft = tableScrollEl.value.scrollLeft;
            }
        });
    }

    function measureAndPlace() {
        updateStickyHeader();

        const el = tableScrollEl.value;
        if (!el || props.dense) {
            showFloatingScrollbar.value = false;
            return;
        }

        contentWidth.value = el.scrollWidth;
        scrollLeft.value = el.scrollLeft;
        isScrolledX.value = el.scrollLeft > 0;

        const overflowsX = el.scrollWidth - el.clientWidth > 1;
        if (!overflowsX) {
            showFloatingScrollbar.value = false;
            return;
        }

        const rect = el.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const nativeBarSize = 18;
        const nativeBarVisible = rect.bottom - nativeBarSize < viewportHeight - 2 && rect.bottom > 8;
        const tableVisible = rect.top < viewportHeight && rect.bottom > 40;

        showFloatingScrollbar.value = tableVisible && !nativeBarVisible;
        floatingScrollStyle.value = {
            left: `${rect.left}px`,
            width: `${rect.width}px`,
        };

        nextTick(() => {
            if (floatingScrollEl.value && tableScrollEl.value && !syncingScroll) {
                floatingScrollEl.value.scrollLeft = tableScrollEl.value.scrollLeft;
            }
        });
    }

    function scheduleMeasure() {
        if (measureRaf) {
            return;
        }

        measureRaf = requestAnimationFrame(() => {
            measureRaf = 0;
            measureAndPlace();
        });
    }

    useEventListener(window, 'scroll', scheduleMeasure, { capture: true, passive: true });
    useEventListener(window, 'resize', scheduleMeasure, { passive: true });
    useResizeObserver(tableScrollEl, scheduleMeasure);
    useResizeObserver(tableEl, scheduleMeasure);

    onMounted(() => {
        nextTick(scheduleMeasure);
    });

    /**
     * 3. Deep watch incoming data changes from parents.
     * Whenever any child component spot-updates a row, this intercepts it
     * and forces the TanStack row model processor to rerun immediately.
     */
    watch(
        () => props.data,
        (newData) => {
            table.setOptions((prev) => ({
                ...prev,
                data: newData,
            }))
            nextTick(scheduleMeasure);
        },
        { deep: true } // Tells Vue to scan deep properties inside the rows
    )

    watch(
        () => props.filters,
        (newFilters) => {
            columnFilters.value = newFilters;
        }
    );

    watch(
        () => [
            table.getState().pagination.pageIndex,
            table.getState().pagination.pageSize,
            table.getRowModel().rows.length,
        ],
        () => nextTick(scheduleMeasure)
    );
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

  .utable-scroll {
    overscroll-behavior-x: contain;
    scrollbar-width: auto;
    scrollbar-color: var(--color-university-blue) color-mix(in srgb, var(--color-brand-light-purple) 30%, white);
  }

  .utable-scroll::-webkit-scrollbar {
    height: 12px;
  }

  .utable-scroll::-webkit-scrollbar-thumb {
    background: var(--color-university-blue);
    border-radius: 6px;
  }

  .utable-scroll::-webkit-scrollbar-track {
    background: color-mix(in srgb, var(--color-brand-light-purple) 20%, white);
  }

  .utable-pin-name {
    position: sticky;
    left: 0;
    z-index: 2;
  }

  tbody td.utable-pin-name {
    background-color: white !important;
  }

  tbody tr:nth-child(odd) td.utable-pin-name {
    background-color: color-mix(in srgb, var(--color-brand-light-purple) 5%, white) !important;
  }

  tbody tr:hover td.utable-pin-name {
    background-color: color-mix(in srgb, var(--color-brand-light-purple) 15%, white) !important;
  }

  .utable-pin-name--scrolled {
    box-shadow: 6px 0 8px -6px rgba(1, 20, 81, 0.35);
  }

  .utable-floating-scroll {
    position: fixed;
    bottom: 0;
    z-index: 40;
    height: 16px;
    overflow-x: auto;
    overflow-y: hidden;
    background: color-mix(in srgb, white 92%, var(--color-brand-light-purple));
    border-top: 1px solid color-mix(in srgb, var(--color-brand-light-purple) 45%, white);
    box-shadow: 0 -2px 8px rgba(1, 20, 81, 0.08);
    scrollbar-width: auto;
    scrollbar-color: var(--color-university-blue) color-mix(in srgb, var(--color-brand-light-purple) 30%, white);
  }

  .utable-floating-scroll::-webkit-scrollbar {
    height: 14px;
  }

  .utable-floating-scroll::-webkit-scrollbar-thumb {
    background: var(--color-university-blue);
    border-radius: 7px;
  }

  .utable-floating-scroll::-webkit-scrollbar-track {
    background: color-mix(in srgb, var(--color-brand-light-purple) 20%, white);
  }

  .utable-floating-scroll-spacer {
    height: 1px;
  }

  .utable-sticky-header {
    position: fixed;
    z-index: 35;
    overflow: hidden;
    background-color: var(--color-university-blue);
    box-shadow: 0 4px 12px rgba(1, 20, 81, 0.22);
  }

  .utable-sticky-header-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
  }

  .utable-sticky-header-scroll::-webkit-scrollbar {
    display: none;
  }
</style>
