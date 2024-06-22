<script setup lang="ts">
import { NSelect } from "naive-ui";
import { AtField} from "atmosphere-ui";
import { format } from "date-fns";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

import { DEFAULT_TIMEZONE, defaultDateFormats } from "@/domains/app/index";

withDefaults(defineProps<{
    modules: any[];
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

        <section>
            <AtField
                class="col-span-6 lg:col-span-4"
                field="roles"
                label="Modules"
            >
                <div class="relative z-0 mt-1 border border-gray-200 grid grid-cols-2 rounded-lg cursor-pointer">
                    <button
                        v-for="module, in modules"
                        type="button"
                        class="relative inline-flex w-full px-4 py-3 rounded-lg focus:z-10 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200"
                        :class="{'border-t border-gray-200 rounded-t-none': module.enabled, 'rounded-b-none': module.enabled}"
                        @click="module.enabled = !module.enabled"
                        :key="module.key"
                    >
                        <div :class="{'opacity-50': !module.enabled}">
                            <!-- Role Name -->
                            <div class="flex items-center">
                                <div class="text-sm text-gray-600" :class="{'font-semibold': module.enabled}">
                                    {{ module.alias }}
                                </div>

                                <svg v-if="module.enabled" class="w-5 h-5 ml-2 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>

                            <!-- Role Description -->
                            <div class="mt-2 text-xs text-gray-600">
                                {{ module.description }}
                            </div>
                        </div>
                    </button>
                </div>
            </AtField>
        </section>

        <slot name="append" />
    </div>
</template>

