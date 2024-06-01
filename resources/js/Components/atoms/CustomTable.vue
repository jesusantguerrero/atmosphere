<script setup lang="ts" generic="T">
import formatMoney from "@/utils/formatMoney";
import CustomCell from "./customCell.js";
import { computed } from "vue";

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
}>(), {
    emptyText: "No data found",
    config() { return { }},
    skeletonLines: 4
})

const getHeaderClass = ({ row } : { row: Record<string, any>}) => {
    return row.headerClass
};

const range = computed(() => {
      return [...Array(props.skeletonLines).keys()];
})
</script>

<template>
    <table
        class="table-fixed"
        style="width: 100%"
        :data="tableData"
        :header-cell-class-name="getHeaderClass"
        @sort-change="$emit('sort', $event)"
        @row-click="$emit('row-click', $event)"
    >
        <thead>
            <tr class="px-2 py-4 font-bold text-left border-b border-gray-200 text-body">
                <th v-for="col in cols"
                 :key="col.name"
                 class="px-2 py-4"
                 :class="[col.headerClass]"
                 :style="{width: col.width, maxWidth: col.maxWidth}"
                >
                    <slot :name="`header-${col.name}`">
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
                :style="{width: col.width, maxWidth: col.maxWidth}">
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
                class="text-body"
                :class="{'bg-base-lvl-2': index % 2}"
            >
                <td v-for="col in cols" :key="col.name" class="h-full align-baseline" :style="{width: col.width, maxWidth: col.maxWidth}">
                    <div class="flex flex-col w-full h-full px-2 py-1 text-left" :class="col.class">
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
                <td :colspan="cols.length" class="flex flex-col w-full">
                    <slot name="empty" v-if="!hideEmptyText">
                        <div class="w-full py-5 text-center text-base-200">
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

