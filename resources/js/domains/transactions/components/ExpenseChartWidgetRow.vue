<script lang="ts" setup>
import { capitalize, computed, watch, ref } from "vue";
import { formatMoney } from "@/utils";
import { NButton, NDataTable, NPopover } from "naive-ui";
import { Link } from "@inertiajs/vue3";

import { ITransactionLine } from "@/domains/transactions/models";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import { h } from "vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { removeTransaction } from "..";

const props = withDefaults(defineProps<{
  item: Record<string, any>;
  title?: string;
  type: 'categories' | 'groups';
  value?: number;
  hideTitle?: boolean;
  classes?: string;
  details?: string;
}>(), {
    classes: "w-full h-full",
});

defineEmits(['selected']);

interface Line {
    id: string,
    line_id: string,
    accountName: string,
    date: string,
    payeeName: string,
    concept: string,
    amount: string,
}
const parseDetails = (details: any[]): any[] => {
    return details?.map?.((row: any): Line | null => {
        console.log({ row })
        return !row ? null : {
            id: row.id,
            line_id: row.line_id,
            accountName: row.name,
            date: row.date,
            payeeName: row.payee_name,
            concept: row.concept,
            amount: formatMoney(row.total),
        }
    }).filter((item: Line | null) => item)
}

const types = {
    categories: 'filter[category_id]',
    groups: 'filter[category_id]'
}
const getCategoryLink = (item: ITransactionLine) => {
    const itemField = types[props.type] ?? types.groups;
    const currentSearch = location.search.replace('?', '&');
    return `/finance/lines?${itemField}=${item.id || item.category_id}${currentSearch}`;
}

const itemDetail = computed(() => {
    return parseDetails(props.details ?? "") ?? []
});

const detailColumn = computed(() => {
    const columns = itemDetail.value.length ? Object.keys(itemDetail.value?.at?.(0)).map(item => ({
        key: item,
        title: capitalize(item)
    })) : [{}]
    columns.push({
        key: 'actions',
        title: 'Actions',
        render: (row) => {
        return h(
          LogerButton,
          {
            strong: true,
            tertiary: true,
            size: 'small',
            onClick: () => removeTransaction(row)
          },
          { default: () => 'Delete' }
        )
      }
    })
    return columns
})
</script>

<template>
    <NPopover trigger="click">
        <template #trigger>
            <p class="inline-flex justify-between px-4 cursor-pointer items-center " :class="classes" @click="$emit('open-details')">
                <span class="font-bold" v-if="!hideTitle">
                    {{ title ?? item.name }}:
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
