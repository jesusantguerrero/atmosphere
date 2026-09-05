<script setup lang="ts" generic="T">
import formatMoney from "@/utils/formatMoney";
import CustomCell from "./customCell.js";
import { computed, ref } from "vue";

export interface TableData {
    [key: string]: string;
}

const props = withDefaults(defineProps<{
    cols: Record<string, any>[],
    tableData: T[],
    emptyText?: string;
    isLoading?: boolean;
    config?: Record<string, any>,
    showPrepend?: boolean;
    showAppend?: boolean;
    hideEmptyText?: boolean;
    skeletonLines?: number;
    rowClass?: (row: T, index: number) => string;
}>(), {
    emptyText: "No data found",
    config() { return { }},
    skeletonLines: 4
})

const emit = defineEmits(['sort', 'row-click']);

const getHeaderClass = ({ row } : { row: Record<string, any>}) => {
    return row.headerClass
};

// Opt-in per column via `sortable: true`. Cycles asc -> desc -> unsorted so the
// user can always get back to the table's natural order (which, for a register,
// is the date order the running balance is computed against).
const sortState = ref<{ name: string | null, dir: 'asc' | 'desc' }>({ name: null, dir: 'asc' });

const toggleSort = (col: Record<string, any>) => {
    if (!col.sortable) return;

    if (sortState.value.name !== col.name) {
        sortState.value = { name: col.name, dir: 'asc' };
    } else if (sortState.value.dir === 'asc') {
        sortState.value = { name: col.name, dir: 'desc' };
    } else {
        sortState.value = { name: null, dir: 'asc' };
    }

    emit('sort', sortState.value);
};

const range = computed(() => {
      return [...Array(props.skeletonLines).keys()];
})

/**
 * Column definitions across the app declare width as a bare number (`200`) or a
 * numeric string (`"100"`). Both are invalid CSS, so the browser dropped them
 * silently and `table-fixed` fell back to distributing every column equally —
 * short columns like a status icon got as much room as a payee name. Units are
 * added here rather than in each cols file so every CustomTable benefits.
 */
const toCssSize = (value: unknown) => {
    if (value === undefined || value === null || value === '') return undefined;

    return typeof value === 'number' || /^\d+(\.\d+)?$/.test(String(value))
        ? `${value}px`
        : String(value);
};

const colStyle = (col: Record<string, any>) => ({
    width: toCssSize(col.width),
    maxWidth: toCssSize(col.maxWidth),
});
</script>

<template>
    <table
        class="table-fixed"
        style="width: 100%"
        :data="tableData"
        :header-cell-class-name="getHeaderClass"
        @row-click="$emit('row-click', $event)"
    >
        <thead>
            <tr class="px-2 py-4 font-bold text-left border-b border-base-lvl-2 text-body">
                <th v-for="col in cols"
                 :key="col.name"
                 class="px-2 py-4"
                 :class="[col.headerClass]"
                 :style="colStyle(col)"
                >
                    <button
                        v-if="col.sortable"
                        type="button"
                        class="inline-flex items-center gap-1 transition-colors hover:text-primary"
                        @click="toggleSort(col)"
                    >
                        <slot :name="`header-${col.name}`">
                            {{ col.label }}
                        </slot>
                        <span
                            class="text-[11px] leading-none"
                            :class="sortState.name === col.name ? 'text-primary' : 'opacity-40'"
                        >
                            {{ sortState.name === col.name ? (sortState.dir === 'asc' ? '▲' : '▼') : '⇅' }}
                        </span>
                    </button>
                    <slot v-else :name="`header-${col.name}`">
                        {{ col.label }}
                    </slot>
                </th>
            </tr>
        </thead>

        <tbody v-if="isLoading" class="animate-pulse">
            <tr v-for="(_data, index) in range"
                :key="`data-row-${index}`"
                class="text-body"
                :class="{'bg-base-lvl-2 py-2': index % 2}"
            >
                <td v-for="col in cols" :key="col.name" class="h-6 py-1"
                :style="colStyle(col)">
                    <span class="inline-block w-full h-full align-baseline bg-base-lvl-1"></span>
                </td>
            </tr>
        </tbody>
        <tbody v-else-if="tableData.length">
            <tr v-if="showPrepend">
                <td :colspan="cols.length">
                    <slot name="prepend">

                    </slot>
                </td>
            </tr>
            <tr v-for="(data, index) in tableData"
                :key="`data-row-${index}`"
                class="text-body transition-colors border-b border-base-lvl-2 hover:bg-base-lvl-1"
                :class="[rowClass?.(data, index) ?? '']"
            >
                <td v-for="col in cols" :key="col.name" class="h-full align-baseline" :style="colStyle(col)">
                    <div class="flex flex-col w-full h-full px-2 py-3 text-left" :class="col.class">
                            <slot :name="col.name" v-bind:scope="{row: data, value: data[col.name], col, field: col.name, $index: index }">
                                <div v-if="col.type == 'calc'" :class="col.class">
                                    {{ col.formula(data) }}
                                </div>

                                <div v-else-if="col.type == 'money'" :class="col.class">
                                    {{ formatMoney(data[col.name]) }}
                                </div>

                                <CustomCell v-else-if="col.render" :class="col.class" :col="col" :data="data" />

                                <div v-else>
                                    {{ data[col.name] }}
                                </div>
                            </slot>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody v-else>
            <tr v-if="showAppend">
                <td :colspan="cols.length">
                    <slot name="append" />
                </td>
            </tr>
            <tr>
                <td :colspan="cols.length" class="w-full">
                    <slot name="empty" v-if="!hideEmptyText">
                        <div class="w-full py-5 text-center text-body">
                            {{ emptyText }}
                        </div>
                    </slot>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
              <td :colspan="cols.length">
                    <slot name="append">

                    </slot>
              </td>
            </tr>
        </tfoot>
    </table>
</template>

