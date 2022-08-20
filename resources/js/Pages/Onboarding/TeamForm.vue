<template>
    <div
        class="w-full px-5 py-4 space-y-5 bg-white rounded-md"
    >
        <AtField class="space-y-2" label="Budget Name">
            <LogerInput placeholder="Team Name" v-model="formData.name" />
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

        <AtField label="Currency Locale" class="md:max-w-sm">
            <NSelect
                v-model:value="formData.currency_symbol_option"
                filterable
                :options="currencyLocaleOptions"
                placeholder="Select"
            />
        </AtField>

        <AtField label="Date Format" class="md:max-w-sm">
            <NSelect
                v-model:value="formData.date_format"
                filterable
                :options="dateFormats"
                placeholder="Select"
            />
        </AtField>

        <slot name="append" />
    </div>
</template>

<script setup>
import { NSelect } from "naive-ui";
import AcceptInvitation from "./AcceptInvitation.vue";
import { AtButton, AtField} from "atmosphere-ui";
import { computed, reactive } from "vue";
import { uniq } from "lodash"
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm } from '@inertiajs/inertia-vue3';
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { format } from "date-fns";
import { DEFAULT_TIMEZONE, defaultDateFormats } from "@/domains/app/index";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

const props = defineProps({
    timezones: {
        type: Array,
        default() {
            return;
        },
    },
    currencies: {
        type: Array,
        default() {
            return []
        }
    },
    formData: {
        type: Object,
        default() {
            return {
                name: '',
                timezone: DEFAULT_TIMEZONE,
                primary_currency_code: 'USD',
                currency_symbol_option: 'before',
                date_format: ''
            }
        }
    }
});

const form = useForm(props.formData);


const currencyCodeFormatter = currency => {
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
