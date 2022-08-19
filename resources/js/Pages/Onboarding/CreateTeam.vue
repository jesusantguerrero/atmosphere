<template>
    <AppLayout :is-onboarding="true">
        <div class="h-auto py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between py-2 mb-10 border-4 border-white rounded-md bg-gray-50"
            >
                <div class="px-5 font-bold text-gray-600">
                    Space Setup
                </div>

                <div  class="flex overflow-hidden font-bold text-gray-500 rounded-lg max-w-min">
                    <AtButton v-if="state.currentMode == 'createTeam'" @click="createOrganization" class="w-48 bg-primary text-white"> Create Space</AtButton>
                </div>
            </div>

            <div
                class="w-full px-5 py-4 space-y-5 bg-white rounded-md "
                v-if="state.currentMode == 'createTeam'"
            >
                <AtField class="space-y-2 md:max-w-sm" label="Budget Name">
                    <LogerInput placeholder="Team Name" v-model="formData.team_name" />
                </AtField>

                <div class="w-full">
                    <div class="md:max-w-sm">
                        <h2 class="my-4 font-bold">Payment Preferences</h2>
                        <AtField label="Timezone" >
                            <NSelect
                                v-model:value="formData.team_timezone"
                                filterable
                                :options="utcTimezones"
                                placeholder="Select"
                            />
                        </AtField>
                    </div>
                </div>
                <div class="w-full">
                    <div class="md:max-w-sm">
                        <AtField label="Primary Currency" >
                            <NSelect
                                v-model:value="formData.team_primary_currency_code"
                                filterable
                                :options="currencyCodes"
                                placeholder="Select"
                            />
                        </AtField>
                    </div>
                </div>
                <div class="w-full">
                    <AtField label="Currency Locale" class="md:max-w-sm">
                        <NSelect
                            v-model:value="formData.team_currency_symbol_option"
                            filterable
                            :options="currencyLocaleOptions"
                            placeholder="Select"
                        />
                    </AtField>
                    <AtField label="Date Format" class="md:max-w-sm">
                        <NSelect
                            v-model:value="formData.team_date_format"
                            filterable
                            :options="dateFormats"
                            placeholder="Select"
                        />
                    </AtField>
                </div>

                <div class="w-full text-right">
                    <button
                        @click="state.currentMode = 'invite'"
                        class="font-bold text-primary/80 underline hover:text-primary"
                    >
                        I have got an invitation
                    </button>
                </div>
            </div>

            <accept-invitation v-else @change="state.currentMode = 'createTeam'" :invitations="invitations" />
        </div>
    </AppLayout>
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
    invitations: {
        type: Array,
        default: () => ([])
    }
});

const state = reactive({
    currentMode: "createTeam",
});

const defaultTimezone = "UTC";

const formData = useForm({
    team_name: '',
    team_timezone: defaultTimezone,
    team_primary_currency_code: 'USD',
    team_currency_symbol_option: 'before',
    team_date_format: ''
});

const utcTimezones = props.timezones.map(timezone => ({
    value: timezone,
    label: timezone
}))

const currencyCodes = props.currencies.map(currency => ({
    value: currency.code,
    label: `${currency.code} ${currency.symbol}`
}))

const date = new Date()
const formats = ['dd MMM, yyyy', 'dd.MM.yyyy', 'MM/dd/yyyy', 'yyyy.MM.dd']
const dateFormats = formats.map((formatString) => ({
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

const createOrganization = () => {
    formData.post('/onboarding')
}
</script>
