<template>
    <section class="px-4 py-2 space">
        <h1>Income vs Expenses</h1>
        <header class="flex mt-4">
            <div v-for="col in cols" :key="col.field" class="w-full font-bold" :class="col.class">
                {{ col.label || col.field }}
            </div>
        </header>

        <Collapse title="Income" title-class="text-success font-bold">
            <template #content>
                <div v-for="income in data.incomeCategories" :key="income.id" class="font-bold">
                    {{ income.name }}
                </div>
            </template>
        </Collapse>

        <Collapse class="mt-4" title="Expenses" title-class="text-error font-bold">
            <template #content>
                <section class="mt-2">
                    <Collapse v-for="(expense, expenseGroupName) in data.expenseCategories" :title="expenseGroupName" title-class="font-bold" :key="expenseGroupName" class="mt-2">
                        <template #content>
                            <div v-for="category in expense" :key="category.id" class="flex space-x-2">
                                <TableCell
                                    v-for="col in cols"
                                    :key="col.field"
                                    class="w-full"
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
            class: "font-bold"
        },
        ...props.data.dateUnits.map((item) => ({
            field: item,
            label: formatMonth(item),
            class: "text-right",
            render: (value) => {
                return formatMoney(value || 0)
            }
        })),
        {
            field: "avg",
            label: "AVG",
            class: "text-right",
            render: (value) => {
                return formatMoney(value || 0)
            }
        }, {
            field: "total",
            label: "Total",
            class: "text-right",
            render: (value) => {
                return formatMoney(value || 0)
            }
        }
    ]
})
</script>
