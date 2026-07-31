<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { format } from "date-fns";
import { NDatePicker } from "naive-ui";
import { AtField, } from "atmosphere-ui";
import axios from "axios";

import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import { IAccount } from "@/domains/transactions/models";
import { formatMoney } from "@/utils";

const emit = defineEmits(['close']);
const props = withDefaults(defineProps<{
    isVisible: boolean;
    account: IAccount,
}>(), {});

// reconciliation
const reconcileForm = useForm({
    isVisible: false,
    date: new Date(),
    balance: 0,
    hasDifference: false,
})

// ── Loger balance as of the picked date ─────────────────────────
// Bank statements are dated: reconciling a two-month-old statement against
// the CURRENT balance always "fails". The picker drives a live lookup of
// what Loger's balance was on that date, so the comparison is honest and
// the user can see the expected difference before saving.
const ledgerBalanceAt = ref<number | null>(null);
const loadingBalance = ref(false);

const fetchBalanceAt = async () => {
    if (!reconcileForm.date) return;
    loadingBalance.value = true;
    try {
        const { data } = await axios.get(`/finance/accounts/${props.account.id}/balance-at`, {
            params: { date: format(reconcileForm.date, 'yyyy-MM-dd') },
        });
        ledgerBalanceAt.value = Number(data.balance);
    } finally {
        loadingBalance.value = false;
    }
};

watch(() => reconcileForm.date, fetchBalanceAt);
watch(() => reconcileForm.hasDifference, (isDetailed) => {
    if (isDetailed) fetchBalanceAt();
});

const previewDifference = computed(() => {
    if (ledgerBalanceAt.value === null) return null;
    return ledgerBalanceAt.value - (Number(reconcileForm.balance) || 0);
});

const previewMatches = computed(() => {
    return previewDifference.value !== null && Math.abs(previewDifference.value) < 0.01;
});

// Statements come from the past — a future-dated reconciliation is meaningless.
const disableFutureDates = (ts: number) => ts > Date.now();

const onClose = () => {
    reconcileForm.reset()
    ledgerBalanceAt.value = null;
    emit('close')
}

const reconciliation = () => {
    reconcileForm.transform(data => ({
        ...data,
        date: format(data.date, 'yyyy-MM-dd'),
    })).post(`/finance/reconciliation/accounts/${props.account.id}`, {
        preserveScroll: true,
        only: ['transactions', 'accounts', 'stats'],
        onFinish() {
            onClose()
        }
    });
};

const doQuickReconciliation = () => {
    reconcileForm.balance = props.account.balance;
    reconciliation()
}


</script>

<template>
<ConfirmationModal
    :show="isVisible"
    @close="onClose"
    :max-width="reconcileForm.hasDifference ? 'md' : 'sm'"
    :title="$t('Ending statement balance')"
>

    <template #content>
        <article v-if="!reconcileForm.hasDifference">
            <h4>{{ $t('Is your current account balance') }}</h4>
            <h2 class="text-lg"> {{ formatMoney(account.balance) }} </h2>
            <p class="text-xs text-body-1/60 mb-2">
                {{ $t('Choose No to reconcile a statement from another date.') }}
            </p>
            <footer class="flex justify-end">
                <LogerButton @click="reconcileForm.hasDifference = true" variant="neutral">
                    {{ $t('No') }}
                </LogerButton>

                <LogerButton
                    class="ml-2"
                    @click="doQuickReconciliation"
                    :class="{ 'opacity-25': reconcileForm.processing }"
                    :disabled="reconcileForm.processing"
                >
                    {{ $t('Yes') }}
                </LogerButton>
            </footer>
        </article>
        <section v-else>
            <h4 class="font-bold">
            {{ account.name }}
            </h4>
            <AtField
            :label="$t('Ending balance Date')"
            class="flex justify-between w-full md:block"
        >

            <NDatePicker
                v-model:value="reconcileForm.date"
                type="date"
                size="large"
                class="w-full"
                :is-date-disabled="disableFutureDates"
            />
        </AtField>

        <!-- What Loger thinks the balance was on that date — the number the
             statement balance will be compared against. -->
        <div class="flex items-center justify-between px-3 py-2 mb-2 rounded-md bg-base-lvl-2 text-sm">
            <span class="text-body-1/60">{{ $t('Loger balance on that date') }}</span>
            <span class="font-semibold tabular-nums">
                <template v-if="loadingBalance">…</template>
                <template v-else-if="ledgerBalanceAt !== null">{{ formatMoney(ledgerBalanceAt, account.currency_code) }}</template>
                <template v-else>—</template>
            </span>
        </div>

        <AtField :label="$t('statement balance')">
            <LogerInput
                ref="input"
                class="opacity-100 cursor-text"
                v-model="reconcileForm.balance"
                :number-format="true"

            >
                <template #prefix>
                    {{ account.currency_code }}
                </template>
            </LogerInput>
        </AtField>

        <!-- Live difference preview: green when the statement matches Loger
             as of that date, red with the gap when it doesn't. -->
        <div
            v-if="previewDifference !== null && Number(reconcileForm.balance)"
            class="flex items-center justify-between px-3 py-2 rounded-md text-sm"
            :class="previewMatches ? 'bg-success/10 text-success' : 'bg-error/10 text-error'"
        >
            <span>{{ previewMatches ? $t('Matches Loger on that date') : $t('Difference') }}</span>
            <span class="font-bold tabular-nums" v-if="!previewMatches">
                {{ formatMoney(Math.abs(previewDifference), account.currency_code) }}
            </span>
            <IMdiCheckCircle v-else class="w-4 h-4" />
        </div>
        </section>

    </template>

    <template #footer v-if="reconcileForm.hasDifference">
        <section class="flex justify-between">
            <LogerButton @click="onClose" variant="neutral">
                {{ $t('Cancel') }}
            </LogerButton>

            <LogerButton
                class="ml-2"
                @click="reconciliation"
                :class="{ 'opacity-25': reconcileForm.processing }"
                :disabled="reconcileForm.processing"
            >
                {{ $t('Save') }}
            </LogerButton>
        </section>
    </template>
</ConfirmationModal>

</template>
