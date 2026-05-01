<template>
  <div>
    <!-- Table -->
    <table class="tw-w-full tw-border-collapse">
      <thead>
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="tw-bg-gray-100">
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            @click="header.column.toggleSorting()"
            class="tw-p-2 tw-text-left tw-border tw-cursor-pointer"
          >
            {{ header.column.columnDef.header }}
            {{ getSortIcon(header.column.getIsSorted()) }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in table.getRowModel().rows" :key="row.id" class="tw-hover:bg-gray-50">
          <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="tw-p-2 tw-border">
            {{ cell.getValue() }}
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination Controls -->
    <div class="tw-mt-4 tw-flex tw-gap-2">
      <button
        @click="table.previousPage()"
        :disabled="!table.getCanPreviousPage()"
        class="tw-px-4 tw-py-2 tw-bg-blue-500 tw-text-white tw-rounded"
      >
        Previous
      </button>
      <button
        @click="table.nextPage()"
        :disabled="!table.getCanNextPage()"
        class="tw-px-4 tw-py-2 tw-bg-blue-500 tw-text-white tw-rounded"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  getPaginationRowModel,
} from '@tanstack/vue-table';

// data.js
const data = [
  { id: 1, name: 'John Doe', age: 28, email: 'john@example.com' },
  { id: 2, name: 'Jane Smith', age: 34, email: 'jane@example.com' },
  { id: 3, name: 'Bob Johnson', age: 45, email: 'bob@example.com' },
];

const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
  },
  {
    accessorKey: 'name',
    header: 'Name',
  },
  {
    accessorKey: 'age',
    header: 'Age',
  },
  {
    accessorKey: 'email',
    header: 'Email',
  },
];

const sortIcons = { asc: '🔼', desc: '🔽' };

const table = useVueTable({
  data,
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
});

function getSortIcon(sortStatus: false | 'asc' | 'desc') {
  if (sortStatus === false) return ''; // or null, or a default icon
  return sortIcons[sortStatus];
}
</script>