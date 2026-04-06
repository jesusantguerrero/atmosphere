<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import WidgetContainer from '@/Components/WidgetContainer.vue';
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';
import { formatMoney } from '@/utils';

import { IAccount } from '@/domains/transactions/models';

const props = defineProps<{
    accounts: IAccount[];
}>();

const { t } = useI18n();

const accountTypeLabels: Record<string, string> = {
    cash: 'Cash',
    bank: 'Bank',
    cash_on_hand: 'Cash on Hand',
    savings: 'Savings',
    credit_card: 'Credit Card',
};

const groupedAccounts = computed(() => {
    const groups: Record<string, IAccount[]> = {};

    for (const account of props.accounts ?? []) {
        const type = (account as any).detail_type?.name ?? 'bank';
        if (!groups[type]) {
            groups[type] = [];
        }
        groups[type].push(account);
    }

    return groups;
});

const groupTotal = (accounts: IAccount[]): number => {
    return accounts.reduce((sum, a) => sum + (a.balance ?? 0), 0);
};

// Start collapsed by default
const expanded = reactive<Record<string, boolean>>({});

const toggleGroup = (groupType: string) => {
    expanded[groupType] = !expanded[groupType];
};

const navigateToAccount = (account: IAccount) => {
    router.visit(`/finance/accounts/${account.id}`);
};
</script>

<template>
    <WidgetContainer :message="$t('Accounts')">
        <template #content>
            <div class="w-full divide-y divide-base">
                <template v-for="(groupAccounts, groupType) in groupedAccounts" :key="groupType">
                    <div class="py-1">
                        <button
                            class="w-full flex items-center justify-between px-5 py-1.5 cursor-pointer hover:bg-base-lvl-2 transition rounded"
                            @click="toggleGroup(String(groupType))"
                        >
                            <div class="flex items-center gap-2">
                                <IMdiChevronRight
                                    class="text-body-1/40 text-xs transition-transform"
                                    :class="{ 'rotate-90': expanded[groupType] }"
                                />
                                <span class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                                    {{ t(accountTypeLabels[groupType] ?? groupType) }}
                                </span>
                            </div>
                            <span
                                class="text-sm font-semibold"
                                :class="groupTotal(groupAccounts) >= 0 ? 'text-success' : 'text-error'"
                            >
                                {{ formatMoney(groupTotal(groupAccounts), groupAccounts[0]?.currency_code) }}
                            </span>
                        </button>
                        <div v-show="expanded[groupType]">
                            <button
                                v-for="account in groupAccounts"
                                :key="account.id"
                                class="w-full flex items-center justify-between px-5 pl-10 py-1.5 hover:bg-base-lvl-2 transition text-left"
                                @click="navigateToAccount(account)"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span
                                        class="w-2 h-2 rounded-full flex-shrink-0"
                                        :style="{ backgroundColor: account.color || '#6366f1' }"
                                    />
                                    <span class="text-sm text-body-1 truncate">{{ account.name }}</span>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                                    <span
                                        class="text-sm font-semibold"
                                        :class="account.balance >= 0 ? 'text-success' : 'text-error'"
                                    >
                                        <MoneyPresenter :value="account.balance" />
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <div v-if="!accounts?.length" class="px-5 py-4 text-sm text-center text-body-1/60">
                    {{ $t('No accounts found.') }}
                </div>
            </div>
        </template>
    </WidgetContainer>
</template>
