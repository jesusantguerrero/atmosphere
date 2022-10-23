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
                    <AtButton :disabled="!formData.name" v-if="state.currentMode == 'createTeam'" @click="createBudget" class="w-48 bg-primary text-white"> Create Space</AtButton>
                </div>
            </div>

            <TeamForm
                class="w-full px-5 py-4 space-y-5 bg-white rounded-md "
                v-if="state.currentMode == 'createTeam'"
                :form-data="formData"
            >
                <template #append>
                    <div class="w-full text-right">
                        <button
                            @click="state.currentMode = 'invite'"
                            class="font-bold text-primary/80 underline hover:text-primary"
                        >
                            I have got an invitation
                        </button>
                    </div>
                </template>
            </TeamForm>

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
import AppLayout from "@/Components/templates/AppLayout.vue";
import { useForm } from '@inertiajs/inertia-vue3';
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { format } from "date-fns";
import TeamForm from "./TeamForm.vue";
import { parseTeamForm } from "@/domains/app";

const props = defineProps({
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
    name: '',
    timezone: defaultTimezone,
    primary_currency_code: 'USD',
    currency_symbol_option: 'before',
    date_format: ''
});


const createBudget = () => {
    formData.transform((data) => {
        data.primary_currency_code = data.primary_currency_code.code || data.primary_currency_code
        return parseTeamForm(data)
    }).post('/onboarding')
}
</script>
