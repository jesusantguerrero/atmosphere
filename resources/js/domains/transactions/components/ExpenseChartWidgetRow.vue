<script lang="ts" setup>
import { computed, h, inject, reactive, ref } from "vue";
import { formatMoney } from "@/utils";
import { NDataTable, NPopover, NSelect } from "naive-ui";
import { Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

import { ITransactionLine } from "@/domains/transactions/models";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import IMdiTrashCanOutline from "~icons/mdi/trash-can-outline";
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
    category_id: number | null,
    accountName: string,
    date: string,
    payeeName: string,
    concept: string,
    amount: string,
}
const parseDetails = (details: any[]): any[] => {
    return details?.map?.((row: any): Line | null => {
        return !row ? null : {
            id: row.id,
            line_id: row.line_id,
            category_id: row.category_id ?? null,
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

const { t } = useI18n();
const categoryOptions = inject<any[]>("categoryOptions", []);

/**
 * Lines recategorized from this popover. A line moved out of the category is
 * hidden right away; the budget numbers refresh with the reload.
 */
const categoryOverrides = reactive<Record<string, number>>({});
const savingLineIds = reactive(new Set<string>());
/** Select menus render inside the popover so picking one does not count as a click outside it. */
const popoverBody = ref<HTMLElement>();

const itemDetail = computed(() => {
    const lines = parseDetails(props.details ?? "") ?? [];
    return lines
        .map((line: Line) => ({ ...line, category_id: categoryOverrides[line.line_id] ?? line.category_id }))
        .filter((line: Line) => props.type !== 'categories' || !categoryOverrides[line.line_id] || line.category_id == props.item.id);
});

const changeCategory = (row: Line, categoryId: number) => {
    if (!categoryId || categoryId == row.category_id) {
        return;
    }

    savingLineIds.add(row.line_id);
    router.patch(`/finance/transaction-lines/${row.line_id}/category`, { category_id: categoryId }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess() {
            categoryOverrides[row.line_id] = categoryId;
        },
        onFinish() {
            savingLineIds.delete(row.line_id);
        },
    });
};

const detailColumn = computed(() => [
    { key: 'date', title: t('Date'), width: 100 },
    { key: 'accountName', title: t('Account'), width: 150, ellipsis: { tooltip: true } },
    {
        key: 'payeeName',
        title: t('Payee'),
        minWidth: 180,
        render: (row: Line) => h('div', { class: 'flex min-w-0 flex-col' }, [
            h('span', { class: 'truncate', title: row.payeeName }, row.payeeName),
            row.concept && row.concept !== row.payeeName
                ? h('span', { class: 'truncate text-xs opacity-70', title: row.concept }, row.concept)
                : null,
        ]),
    },
    { key: 'amount', title: t('Amount'), width: 120, align: 'right' as const, className: 'whitespace-nowrap' },
    {
        key: 'category_id',
        title: t('Category'),
        width: 200,
        render: (row: Line) => h(NSelect, {
            value: row.category_id,
            options: categoryOptions,
            filterable: true,
            size: 'small',
            loading: savingLineIds.has(row.line_id),
            disabled: savingLineIds.has(row.line_id),
            consistentMenuWidth: false,
            to: popoverBody.value ?? false,
            onUpdateValue: (categoryId: number) => changeCategory(row, categoryId),
        }),
    },
    {
        key: 'actions',
        title: '',
        width: 48,
        align: 'center' as const,
        render: (row: Line) => h(
            'button',
            {
                type: 'button',
                class: 'rounded p-1 opacity-60 transition hover:bg-error/10 hover:text-error hover:opacity-100',
                title: t('Delete'),
                onClick: () => removeTransaction(row as any),
            },
            h(IMdiTrashCanOutline)
        ),
    },
]);
</script>

<template>
    <NPopover trigger="click">
        <template #trigger>
            <p class="inline-flex cursor-pointer items-center" :class="[classes, hideTitle ? 'justify-end pl-4' : 'justify-between px-4']" @click="$emit('open-details')">
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
        <section ref="popoverBody" class="relative flex w-[860px] max-w-[90vw] flex-col gap-4">
            <SectionTitle class="flex items-center"> {{ $t('Transaction history') }}
                <Link
                    class="flex items-center ml-4 hover:underline group hover:text-primary"
                    :href="getCategoryLink(item)">
                    Total: {{  formatMoney(value ?? item.total) }}
                    <IMdiLink class="invisible ml-1 group-hover:visible" />
                </Link>
            </SectionTitle>
            <NDataTable
                size="small"
                :columns="detailColumn"
                :data="itemDetail"
                :max-height="320"
                :scroll-x="820"
            />
        </section>
    </NPopover>
</template>
