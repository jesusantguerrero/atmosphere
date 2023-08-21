<script lang="ts" setup>
import { capitalize, computed } from "vue";
import { formatMoney } from "@/utils";
import { NDataTable, NPopover } from "naive-ui";
import { Link } from "@inertiajs/vue3";
import { ITransactionLine } from "../Modules/finance/models/transactions";

const { item, type  }= defineProps<{
  item: Record<string, any>;
  title?: string;
  type: 'categories' | 'groups';
}>();

const emit = defineEmits(['selected']);


interface Line {
    id: string,
    accountName: string,
    date: string,
    payeeName: string,
    concept: string,
    amount: string,
}
const parseDetails = (details: string): any[] => {
    return !details ? [] : details.split('|')
    .map((row: any): Line | null => {
        const rowData = row.split(':');
        return !rowData ? null : {
            id: rowData[0],
            accountName: rowData[1],
            date: rowData[2],
            payeeName: rowData[3],
            concept: rowData[4],
            amount: rowData[5],
        }
    }).filter((item: Line | null) => item)
}

const types = {
    categories: 'category_id',
    groups: 'group_id'
}
const getCategoryLink = (item: ITransactionLine) => {
    const itemField = types[type] ?? types.groups;
    const currentSearch = location.search.replace('?', '&');
    return `/finance/lines?${itemField}=${item.id || item.category_id}${currentSearch}`;
}

const itemDetail = computed(() => {
    return parseDetails(item.details) ?? []
})

const detailColumn = computed(() => {
    return itemDetail.value.length ? Object.keys(itemDetail.value?.at?.(0)).map(item => ({
        key: item,
        title: capitalize(item)
    })) : [{}]
})
</script>

<template>
    <NPopover trigger="click">
        <template #trigger>
            <p class="flex justify-between w-full px-4 cursor-pointer">
                <span class="font-bold">
                    {{ item.name }}:
                </span>
                <Link
                class="flex items-center ml-4 hover:underline group hover:text-primary"
                :href="getCategoryLink(item)">
                    {{  formatMoney(item.total) }}
                    <IMdiLink class="invisible ml-1 group-hover:visible" />
                </Link>
            </p>
        </template>
        <div class="">
            <NDataTable
                :columns="detailColumn"
                :data="itemDetail"
            />
        </div>
    </NPopover>
</template>
../../domains/transactions/models/transactions