<script setup lang="ts">
import { router, useForm} from "@inertiajs/vue3";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { ElMessageBox } from "element-plus";
import LogerButton from "@/Components/atoms/LogerButton.vue";

// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";

import { formatMoney } from "@/utils";
import { IAccount } from "@/domains/transactions/models";
import AccountReconciliationAlert from "@/domains/transactions/components/AccountReconciliationAlert.vue";


const { account } = defineProps<{
    account: IAccount;
}>();

const { t } = useI18n();

const toCents = (value: unknown): number => Math.round(Number(value || 0) * 100);

const goToReconciliation = () => {
    router.visit(`/finance/reconciliation/${account?.reconciliation_last?.id}`)
}

const isMatched = computed(() => {
    return toCents(account.balance) === toCents(account.reconciliation_last?.amount);
})

const adjustmentForm = useForm({
    date: account.reconciliation_last?.date,
    balance: account.balance,
})
const adjustAndFinish = async () => {
    if (!isMatched.value) {
        const isConfirmed = await ElMessageBox.confirm(
            t("This records an adjustment of {amount} so Loger matches your bank statement.", { amount: formatMoney(differenceAmount.value) }),
            t("Save adjustment and finish"),
            { confirmButtonText: t("Save adjustment"), cancelButtonText: t("Cancel") },
        ).catch(() => false);
        if (!isConfirmed) return;

        adjustmentForm.put(`/finance/reconciliation/${account?.reconciliation_last?.id}/save-adjustment`)
    } else {
        adjustmentForm
    .transform((data) => ({
      ...data,
      date: account.reconciliation_last?.date,
    }))
    .put(`/finance/reconciliation/${account.reconciliation_last?.id}`, {
      onFinish() {
        adjustmentForm.reset();
      },
    });
    }
}

const differenceStateText = computed(() => {
    return toCents(account.balance) > toCents(account.reconciliation_last?.amount) ? t('higher') : t('lower')
})

const differenceAmount = computed(() => {
    return Math.abs(toCents(account.reconciliation_last?.amount) - toCents(account.balance)) / 100
})

const hasPendingReconciliation = computed(() => {
    return account.reconciliation_last?.status  == 'pending';
})

</script>

<template>
    <section class="flex justify-between w-full px-4 py-2 text-white cursor-pointer bg-secondary"
        v-if="hasPendingReconciliation"
    >
        <article class="flex items-center">
            <AccountReconciliationAlert class="mr-2 text-white" />
            <p v-if="!isMatched">
                {{ $t('Difference of') }}
                <strong>{{ formatMoney(differenceAmount) }}</strong>
                — {{ $t('Loger balance is {state} than your bank statement.', { state: differenceStateText }) }}
            </p>
            <p v-else>
                {{ $t('Reconciliation is balanced and ready to complete.') }}
            </p>
        </article>
        <article class="flex space-x-2">
            <LogerButton
                variant="secondary"
                v-if="!isMatched"
                @click="goToReconciliation()"
                :disabled="adjustmentForm.processing"
                :processing="adjustmentForm.processing"
            >
                {{ $t('Review') }}
            </LogerButton>
            <LogerButton variant="secondary"
                @click="adjustAndFinish()"
                :disabled="adjustmentForm.processing"
                :processing="adjustmentForm.processing"
            >
                <template #icon>
                    <IMdiCheck />
                </template>
                {{ isMatched ? $t('Finish reconciliation') : $t('Save adjustment and finish') }}
            </LogerButton>
        </article>
    </section>
</template>
