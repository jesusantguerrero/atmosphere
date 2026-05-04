<script setup lang="ts">
import { NSelect } from "naive-ui";
import { AtField} from "atmosphere-ui";
import { format } from "date-fns";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

import { DEFAULT_TIMEZONE, defaultDateFormats } from "@/domains/app/index";

withDefaults(defineProps<{
    formData: Object;
}>(), {
    formData: () => ({
        name: '',
        timezone: DEFAULT_TIMEZONE,
        primary_currency_code: 'USD',
        currency_symbol_option: 'before',
        date_format: ''
    })
});

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
    label: 'After'
}, {
    value: 'before',
    label: 'Before'
}, {
    value: 'without_symbol',
    label: 'Without Symbol'
}]
</script>


<template>
    <div
        class="w-full px-5 py-4 space-y-5 bg-white rounded-md"
    >
        <AtField class="space-y-2" label="Budget Name">
            <LogerInput placeholder="Eg. Family" v-model="formData.name" required />
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

        <AtField label="Timezone">
            <LogerApiSelect
                v-model="formData.timezone"
                placeholder="Select"
                endpoint="/api/timezones"
                once
                :tag="false"
            />
        </AtField>

        <AtField label="Primary Currency" >
            <LogerApiSelect
                v-model="formData.primary_currency_code"
                placeholder="Select"
                endpoint="/api/currencies"
                once
                track-by="code"
                :customLabel="currencyCodeFormatter"
                :tag="false"
            />
        </AtField>

        <section class="flex space-x-4">
            <AtField label="Currency Locale" class="md:w-full">
                <NSelect
                    v-model:value="formData.currency_symbol_option"
                    filterable
                    :options="currencyLocaleOptions"
                    placeholder="Select"
                />
            </AtField>

            <AtField label="Date Format" class="md:w-full">
                <NSelect
                    v-model:value="formData.date_format"
                    filterable
                    :options="dateFormats"
                    placeholder="Select"
                />
            </AtField>
        </section>

        <slot name="append" />
    </div>
</template>

