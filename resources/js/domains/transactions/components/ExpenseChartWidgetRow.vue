<script lang="ts" setup>
import { capitalize, computed } from "vue";
import { formatMoney } from "@/utils";
import { NDataTable, NPopover } from "naive-ui";
import { Link } from "@inertiajs/vue3";

import { ITransactionLine } from "@/domains/transactions/models";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

const {
    item,
    type,
    classes = "w-full h-full"
} = defineProps<{
  item: Record<string, any>;
  title?: string;
  type: 'categories' | 'groups';
  value?: number;
  hideTitle?: boolean;
  classes?: string
}>();

defineEmits(['selected']);

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
            <p class="inline-flex justify-between px-4 cursor-pointer " :class="classes">
                <span class="font-bold" v-if="!hideTitle">
                    {{ title ?? item.name }}: {{ classes }}
                </span>
                <span
                    class="flex items-center ml-4 hover:underline group hover:text-primary"
                    :href="getCategoryLink(item)">
                    {{  formatMoney(value ?? item.total) }}
                    <IMdiLink class="invisible ml-1 group-hover:visible" />
                </span>
            </p>
        </template>
        <section class="h-96 w-[900px] overflow-hidden">
            <SectionTitle class="flex items-center"> Transaction history
                <Link
                    class="flex items-center ml-4 hover:underline group hover:text-primary"
                    :href="getCategoryLink(item)">
                    Total: {{  formatMoney(value ?? item.total) }}
                    <IMdiLink class="invisible ml-1 group-hover:visible" />
                </Link>
            </SectionTitle>
            <NDataTable
                class="mt-6"
                :columns="detailColumn"
                :data="itemDetail"
                :max-height="250"
            />
        </section>
    </NPopover>
</template>
