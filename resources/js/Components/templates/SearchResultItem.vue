<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { formatDate, formatMoney } from '@/utils';
import { TRANSACTION_DIRECTIONS } from '@/domains/transactions';

defineProps<{
    item: Record<string, any>;
    active: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <section
        class="flex items-center justify-between w-full gap-3 px-4 py-2 text-left rounded-md cursor-pointer"
        :class="active ? 'bg-base-lvl-2' : 'hover:bg-base-lvl-2'"
    >
        <template v-if="item.type == 'transactions'">
            <div class="min-w-0">
                <p class="text-sm font-medium truncate text-body-1">{{ item.title }}</p>
                <p class="text-xs truncate text-body-1/60">
                    {{ [item.subtitle, item.meta].filter(Boolean).join(' · ') || t('Transaction') }}
                </p>
            </div>
            <div class="text-right shrink-0">
                <p
                    class="text-sm font-bold"
                    :class="item.direction == TRANSACTION_DIRECTIONS.DEPOSIT ? 'text-success' : 'text-error'"
                >
                    {{ formatMoney(item.total, item.currency_code) }}
                </p>
                <p class="text-xs text-body-1/60">{{ formatDate(item.date) }}</p>
            </div>
        </template>

        <template v-else-if="item.type == 'payees'">
            <div class="min-w-0">
                <p class="text-sm font-medium truncate text-body-1">{{ item.title }}</p>
                <p class="text-xs text-body-1/60">{{ t('Payee') }}</p>
            </div>
            <IMdiChevronRight class="shrink-0 text-body-1/40" />
        </template>

        <p v-else class="text-sm truncate text-body-1">{{ item.title }}</p>
    </section>
</template>
