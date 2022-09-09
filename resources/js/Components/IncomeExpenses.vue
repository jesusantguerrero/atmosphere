<template>
    <section class="px-4 py-2 divide-y">
        <header class="flex mt-4 bg-base px-4 py-2">
            <div v-for="col in cols" :key="col.field" class="w-full font-bold capitalize" :class="col.class">
                {{ col.label || col.field }}
            </div>
        </header>

        <Collapse
            title="Income"
            title-class="text-success font-bold bg-base-lvl-1 hover:bg-base p-2"
            content-class="pb-4"
            :gap="false"
        >
            <template #content>
                <div v-for="income in data.incomeCategories" :key="income.id" class="flex space-x-2 group hover:bg-primary/5">
                    <TableCell
                        v-for="col in cols"
                        :key="col.field"
                        class="w-full px-2 py-2 cursor-pointer hover:font-bold hover:text-primary transition"
                        :class="{'group-hover:text-primary': col.field == 'name'}"
                        :title="col.label"
                        :col="col"
                        :value="data.incomes[income.name][col.field]"
                    />
                </div>
            </template>
        </Collapse>

        <Collapse
            title="Expenses"
            title-class="text-error font-bold bg-base-lvl-1 hover:bg-base p-2"
            content-class="pb-4"
            :gap="false"
        >
            <template #content>
                <section class="mt-2">
                    <Collapse v-for="(expense, expenseGroupName) in data.expenseCategories" :title="expenseGroupName" title-class="font-bold bg-base-lvl-2 py-1 rounded-md px-2" :key="expenseGroupName" class="mt-2" :gap="false">
                        <template #content>
                            <div v-for="category in expense" :key="category.id" class="flex space-x-2 group hover:bg-primary/5">
                                <TableCell
                                    v-for="col in cols"
                                    :key="col.field"
                                    class="w-full px-2 py-2 cursor-pointer hover:font-bold hover:text-primary transition"
                                    :class="{'group-hover:text-primary': col.field == 'name'}"
                                    :title="col.label"
                                    :col="col"
                                    :value="data.expenses[`${expenseGroupName}:${category.name}`][col.field]"
                                />
                            </div>
                        </template>
                    </Collapse>
                </section>
            </template>
        </Collapse>
    </section>
</template>

<script setup>
import { useCollapse } from '@/composables/useCollapse';
import { computed, ref } from 'vue';

import Collapse from '@/Components/molecules/Collapse.vue';
import TableCell from './TableCell.vue';

import formatMoney from '@/utils/formatMoney';
import { formatMonth } from '@/utils';

const props = defineProps({
    data: {
        type: Object
    }
})

const incomeRef = ref();
const { isCollapsed, icon, toggleCollapse } = useCollapse(incomeRef);

const cols = computed(() => {
    return [
        {
            field: "name",
            class: "font-bold text-sm"
        },
        ...props.data.dateUnits.map((item) => ({
            field: item,
            label: formatMonth(item),
            class: "text-right text-sm",
            render: (value) => {
                return formatMoney(value || 0)
            }
        })),
        {
            field: "avg",
            label: "AVG",
            class: "text-right text-sm",
            render: (value) => {
                return formatMoney(value || 0)
            }
        }, {
            field: "total",
            label: "Total",
            class: "text-right text-sm",
            render: (value) => {
                return formatMoney(value || 0)
            }
        }
    ]
})
</script>
