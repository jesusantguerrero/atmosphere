<template>
    <NPopover trigger="manual" placement="bottom" :show="showPopover">
        <template #trigger>
            <AtButton @click="showPopover = !showPopover" class="flex items-center space-x-2 text-sm text-white transition shadow-md hover:shadow-md hover:shadow-primary hover:ring-4 ring-offset-2 ring-primary/20 bg-primary" rounded>
                <div class="flex items-center justify-center py-1 rounded-md">
                    <IMdiPlus />
                </div>
            </AtButton>
        </template>

        <div class="py-1 w-96">
            <BudgetGroupForm
                v-model="transactionCommand"
                mode="edit"
                @save="onSave"
                :show-cancel="false"
                placeholder=""
            />
            <span>
                Example: #Rent $10000 from Nomina on 5/28
            </span>
        </div>
    </NPopover>
</template>

<script setup lang="ts">
    import { AtButton } from 'atmosphere-ui';
    import { ref } from 'vue';
    import { NPopover } from 'naive-ui';
    import { DateTime } from "luxon";

    import BudgetGroupForm from '@/Components/molecules/BudgetGroupForm.vue';

    import { TRANSACTION_DIRECTIONS } from '@/domains/transactions';
    import { useTransactionStore } from '@/store/transactions';

    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const showPopover = ref(false);

    const transactionCommand = ref("");
    const PATTERN = /([\d{1,2}\/\d{1,2}])?.*?#([\w\s]+)\b.*?\$(\d+)?.*?\bfrom\b\s+(\w+)?.*?\bto\b\s+(\w+\s+\w+)/


    const store = useTransactionStore()
    const onSave = () => {
        const match = transactionCommand.value.match(PATTERN);
        // Extract the variables from the captured groups
        let date = match[1]?.split('/') ?? [];
        const categoryName = match[2] || '';
        const amount = match[3] || '';
        const accountName = match[4];
        const counterAccountName = match[4];

        date = date.length ? DateTime.fromFormat(date.join('/'), date.length == 3 ? "yyyy/MM/dd" : "MM/dd") : null;

        if (amount || accountName) {
            alert("Bad amount and account")
        }

        store.onSubmit({
            date: date ?? new Date(),
            categoryName,
            accountName,
            amount: Number(amount ?? 0),
            counterAccountName
        })

        transactionCommand.value = "";
    }
</script>
