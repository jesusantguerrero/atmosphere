<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { NSelect } from "naive-ui";
import { AtField} from "atmosphere-ui";
import { format } from "date-fns";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

import { DEFAULT_TIMEZONE, defaultDateFormats } from "@/domains/app/index";
import { getCurrencyByCode } from "@/domains/transactions/currency-constants";

const { t } = useI18n();

const props = withDefaults(defineProps<{
    formData: Record<string, any>;
    accounts?: Record<string, any>[];
}>(), {
    formData: () => ({
        name: '',
        timezone: DEFAULT_TIMEZONE,
        primary_currency_code: 'USD',
        currency_symbol_option: 'before',
        date_format: '',
        cash_withdrawal_account_id: ''
    }),
    accounts: () => []
});

// Only asset accounts make sense as a cash-withdrawal destination; credit cards
// are excluded so the picker isn't cluttered with accounts money never lands in.
const cashAccountOptions = computed(() => (props.accounts ?? [])
    .filter((account) => !account.credit_closing_day)
    .map((account) => ({ value: String(account.id), label: account.name })));

const currencyCodeFormatter = (currency: Record<string, string>) => {
    return currency.code ? `${currency.code} ${currency.symbol}` : currency.name ?? currency;
}

const date = new Date()
const dateFormats = defaultDateFormats.map((formatString) => ({
    value: formatString,
    label: format(date, formatString)
}))

const languageOptions = [
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Español' },
]

// Computed, not a plain const: this form renders before the space's language is
// applied, so a snapshot taken at setup kept showing the English labels.
const currencyLocaleOptions = computed(() => [{
    value: 'after',
    label: t('After')
}, {
    value: 'before',
    label: t('Before')
}, {
    value: 'without_symbol',
    label: t('Without Symbol')
}])

// An empty date format left the picker blank on first render, which reads as an
// unanswered required field. Seed the first supported format instead.
onMounted(() => {
    if (!props.formData.date_format) {
        props.formData.date_format = defaultDateFormats[0];
    }
});

// The currency select can hand back either a plain code or the whole API record,
// depending on where the form is mounted — normalize before formatting.
const currencyCode = computed(() => {
    const value = props.formData.primary_currency_code;
    return (typeof value === 'object' ? value?.code : value) || 'USD';
});

const currencySymbol = computed(() => {
    return getCurrencyByCode(currencyCode.value)?.symbol ?? currencyCode.value;
});

// Live readback of the money + date choices. Cheaper than making the user save
// and hunt for a number somewhere in the app to see what they picked.
const amountPreview = computed(() => {
    const amount = (1234.56).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    switch (props.formData.currency_symbol_option) {
        case 'without_symbol': return amount;
        case 'after': return `${amount} ${currencySymbol.value}`;
        default: return `${currencySymbol.value} ${amount}`;
    }
});

const datePreview = computed(() => {
    try {
        return format(date, props.formData.date_format || defaultDateFormats[0]);
    } catch (err) {
        return format(date, defaultDateFormats[0]);
    }
});
</script>


<template>
    <div class="w-full px-5 py-4 space-y-6 bg-base-lvl-3 rounded-md">
        <AtField class="space-y-2" :label="$t('Space name')">
            <LogerInput :placeholder="$t('Eg. Family')" v-model="formData.name" required />
            <p class="mt-1 text-xs text-body-1/60">
                {{ $t('The household everyone shares. You can rename it later.') }}
            </p>
        </AtField>

        <section class="space-y-3">
            <header class="flex items-center gap-3">
                <h4 class="text-xs font-bold tracking-wide uppercase text-body-1/50 whitespace-nowrap">
                    {{ $t('Region') }}
                </h4>
                <span class="flex-1 h-px bg-base-lvl-2" />
            </header>

            <div class="grid gap-x-4 md:grid-cols-2">
                <AtField :label="$t('Language')">
                    <NSelect
                        v-model:value="formData.language"
                        :options="languageOptions"
                        :placeholder="$t('Select')"
                    />
                </AtField>

                <AtField :label="$t('Timezone')">
                    <LogerApiSelect
                        v-model="formData.timezone"
                        :placeholder="$t('Select')"
                        endpoint="/api/timezones"
                        once
                        :tag="false"
                    />
                </AtField>
            </div>
        </section>

        <section class="space-y-3">
            <header class="flex items-center gap-3">
                <h4 class="text-xs font-bold tracking-wide uppercase text-body-1/50 whitespace-nowrap">
                    {{ $t('Money and dates') }}
                </h4>
                <span class="flex-1 h-px bg-base-lvl-2" />
            </header>

            <!-- Currency leads (it drives the symbol), then the two knobs that
                 only change how it renders. -->
            <AtField :label="$t('Primary Currency')">
                <LogerApiSelect
                    v-model="formData.primary_currency_code"
                    :placeholder="$t('Select')"
                    endpoint="/api/currencies"
                    once
                    track-by="code"
                    :customLabel="currencyCodeFormatter"
                    :tag="false"
                />
            </AtField>

            <div class="grid gap-x-4 md:grid-cols-2">
                <AtField :label="$t('Symbol position')">
                    <NSelect
                        v-model:value="formData.currency_symbol_option"
                        filterable
                        :options="currencyLocaleOptions"
                        :placeholder="$t('Select')"
                    />
                </AtField>

                <AtField :label="$t('Date Format')">
                    <NSelect
                        v-model:value="formData.date_format"
                        filterable
                        :options="dateFormats"
                        :placeholder="$t('Select')"
                    />
                </AtField>
            </div>

            <!-- Live readback of both choices at once. -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-3 py-2 text-sm rounded-md bg-base-lvl-2">
                <span class="text-xs font-semibold tracking-wide uppercase text-body-1/50">
                    {{ $t('Preview') }}
                </span>
                <span class="font-bold text-body-1">{{ amountPreview }}</span>
                <span class="text-body-1/30">·</span>
                <span class="font-bold text-body-1">{{ datePreview }}</span>
            </div>
        </section>

        <section v-if="cashAccountOptions.length" class="space-y-3">
            <header class="flex items-center gap-3">
                <h4 class="text-xs font-bold tracking-wide uppercase text-body-1/50 whitespace-nowrap">
                    {{ $t('Automation') }}
                </h4>
                <span class="flex-1 h-px bg-base-lvl-2" />
            </header>

            <AtField :label="$t('Cash withdrawal account')">
                <NSelect
                    v-model:value="formData.cash_withdrawal_account_id"
                    filterable
                    clearable
                    :options="cashAccountOptions"
                    :placeholder="$t('Select')"
                />
                <p class="mt-1 text-xs text-secondary">
                    {{ $t('ATM/cash-withdrawal emails are routed here as a transfer instead of an expense.') }}
                </p>
            </AtField>
        </section>

        <slot name="append" />
    </div>
</template>
