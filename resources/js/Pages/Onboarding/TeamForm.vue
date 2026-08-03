<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { NSelect } from "naive-ui";
import { AtField} from "atmosphere-ui";
import { format } from "date-fns";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

import { DEFAULT_TIMEZONE, defaultDateFormats } from "@/domains/app/index";

const { t } = useI18n();

const props = withDefaults(defineProps<{
    formData: Object;
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

const currencyLocaleOptions = [{
    value: 'after',
    label: t('After')
}, {
    value: 'before',
    label: t('Before')
}, {
    value: 'without_symbol',
    label: t('Without Symbol')
}]
</script>


<template>
    <div
        class="w-full px-5 py-4 space-y-5 bg-base-lvl-3 rounded-md"
    >
        <AtField class="space-y-2" :label="$t('Space name')">
            <LogerInput :placeholder="$t('Eg. Family')" v-model="formData.name" required />
        </AtField>

        <AtField :label="$t('Language')">
            <select
                v-model="formData.language"
                class="w-full px-3 py-2 border rounded-md bg-base-lvl-3 border-base-lvl-2 focus:outline-none focus:ring focus:ring-primary"
            >
                <option value="en">English</option>
                <option value="es">Español</option>
            </select>
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

        <AtField :label="$t('Primary Currency')" >
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

        <section class="flex space-x-4">
            <AtField :label="$t('Symbol position')" class="md:w-full">
                <NSelect
                    v-model:value="formData.currency_symbol_option"
                    filterable
                    :options="currencyLocaleOptions"
                    :placeholder="$t('Select')"
                />
            </AtField>

            <AtField :label="$t('Date Format')" class="md:w-full">
                <NSelect
                    v-model:value="formData.date_format"
                    filterable
                    :options="dateFormats"
                    :placeholder="$t('Select')"
                />
            </AtField>
        </section>

        <AtField
            v-if="cashAccountOptions.length"
            :label="$t('Cash withdrawal account')"
        >
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

        <slot name="append" />
    </div>
</template>
