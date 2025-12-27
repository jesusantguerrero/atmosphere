<script setup lang="ts">
import { computed, reactive, watch, ref } from 'vue';
import ExactMath from "@/plugins/exactMath"

import TransactionCard from "@/Components/molecules/TransactionCard.vue";
import SectionCard from '@/Components/molecules/SectionCard.vue';

import formatMoney from "@/utils/formatMoney";
import { ITransaction } from '@/domains/transactions/models/transactions';

const props = withDefaults(defineProps<{
    classes?: string;
    tableClass?: string;
    tableLabel?: string;
    transactions: any[];
    parser: Function | null;
    showSum?: boolean;
    allowMarkAsPaid?: boolean;
    allowMarkAsApproved?: boolean;
    allowRemove?: boolean;
    allowMatch?: boolean;
    allowSelect?: boolean;
    allowEdit?: boolean
}>(), {
    classes: "mt-1",
    tableClass: "mt-2 bg-base-lvl-1 border border-base-deep-1 rounded-lg shadow-md",
    transactions: () => ([]),
    parser:  null,
    allowSelect: true,
})

const emit = defineEmits(['update:selected', 'edit', 'paid-clicked', 'approved', 'removed']);
const transactionsParsed = computed(() => {
    return !props.parser ? props.transactions : props.parser(props.transactions);
})

watch(() => props.transactions, () => {
    console.log(" I have changed", props.transactions)
})

const selectedItems: number[]|string[] = reactive<number[]|string[]>([]);
const lastSelectedIndex = ref<number>(-1);

const isSelected = (transaction: ITransaction) => {
    // @ts-expect-error: includes inst available they said
    return selectedItems?.includes(transaction.id || transaction.title);
}

const handleSelect = (transaction: ITransaction, index: number, event: MouseEvent) => {
    if (!props.allowSelect) return;

    const id: number| string = transaction.id || transaction.title;

    if (event.shiftKey && lastSelectedIndex.value !== -1) {
        // Shift+click: select range
        const start = Math.min(lastSelectedIndex.value, index);
        const end = Math.max(lastSelectedIndex.value, index);

        for (let i = start; i <= end; i++) {
            const itemId = transactionsParsed.value[i].id || transactionsParsed.value[i].title;
            if (!selectedItems.includes(itemId)) {
                selectedItems.push(itemId);
            }
        }
    } else {
        // Regular click: toggle selection
        if (isSelected(transaction)) {
            selectedItems.splice(selectedItems.indexOf(id), 1);
        } else {
            selectedItems.push(id);
        }
    }

    lastSelectedIndex.value = index;
    emit('update:selected', selectedItems);
}

const selectedSum = computed(() => {
    return calculateSum(selectedItems as ITransaction);
})
const calculateSum = (items: number[]|string[]) => {
     return items.reduce((sum: Record<string, number>, id: number|string) => {
        const transaction = transactionsParsed.value.find(
            (transaction: ITransaction) => transaction.id === id || transaction.title === id);
            if (transaction) {
            if (sum[transaction.currencyCode]) {
                sum[transaction.currencyCode] = ExactMath.add(sum[transaction.currencyCode],  transaction.value);
            } else {
                sum[transaction.currencyCode] = ExactMath.add(0, transaction.value);
            }
        }
        return sum;
    }, {});
}
</script>

<template>
<SectionCard :classes="classes" :section-title="tableLabel" :card-class="tableClass">
    <template #action>
        <slot name="action" />
    </template>
    <template #top>
        <slot name="top" />
    </template>

    <div>
        <template v-if="transactionsParsed.length">
            <TransactionCard
                v-for="(transaction, index) in transactionsParsed"
                v-bind="transaction"
                :mark-as-paid="allowMarkAsPaid"
                :mark-as-approved="allowMarkAsApproved"
                :allow-remove="allowRemove"
                :allow-select="allowSelect"
                :allow-match="allowMatch"
                :allow-edit="allowEdit"
                :key="transaction.id"
                :isSelected="isSelected(transaction)"
                class="odd:bg-base-lvl-1 even:bg-base-lvl-2"
                @selected="handleSelect(transaction, index, $event)"
                @paid-clicked="$emit('paid-clicked', transaction)"
                @approved="$emit('approved', transactions[index])"
                @removed="$emit('removed', transactions[index])"
                @edit="$emit('edit', transactions[index])"
            />
            <div class="flex items-center justify-between px-4 py-5 bg-base-lvl-1 text-body-1" v-if="showSum">
                <div class="font-bold">Selected Items sum</div>
                <div class="space-y-1 text-right">
                    <div v-for="(currencySum) in selectedSum">
                    {{ formatMoney(currencySum) }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="items-center justify-center px-5 py-6 text-center text-gray-600">
            <div class="mx-auto text-6xl">
               <svg xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="mx-auto iconify iconify--iconoir" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M7 2h9.5L21 6.5V19"></path><path d="M3 20.5v-14A1.5 1.5 0 0 1 4.5 5h9.752a.6.6 0 0 1 .424.176l3.148 3.148A.6.6 0 0 1 18 8.75V20.5a1.5 1.5 0 0 1-1.5 1.5h-12A1.5 1.5 0 0 1 3 20.5Z"></path><path fill="currentColor" d="M14 8.4V5.354a.354.354 0 0 1 .604-.25l3.292 3.292a.353.353 0 0 1-.25.604H14.6a.6.6 0 0 1-.6-.6Z"></path></g></svg>
            </div>
            <span class="mt-10 text-lg font-bold">
                No transactions found
            </span>
        </div>
    </div>
</SectionCard>
</template>
