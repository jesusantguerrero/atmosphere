<script setup lang="ts">
import { reactive } from "vue";
import { useForm, usePage } from '@inertiajs/vue3';

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import AcceptInvitation from "./AcceptInvitation.vue";
import TeamForm from "./TeamForm.vue";

import { parseTeamForm } from "@/domains/app";

defineProps({
    invitations: {
        type: Array,
        default: () => ([])
    }
});

const state = reactive({
    currentMode: "createTeam",
});

const defaultTimezone = "UTC";

const userLanguage = (usePage().props.auth?.user?.language as string) ?? 'en';

const formData = useForm({
    name: '',
    timezone: defaultTimezone,
    primary_currency_code: 'USD',
    currency_symbol_option: 'before',
    date_format: '',
    language: userLanguage,
    modules: [] as string[],
});

// Opt-in modules shown at Space Setup. Finance is always on (the door); these
// are the family-OS pillars the user can light up now instead of hunting for a
// buried toggle later. Names match CoreModuleTypeEnum cases (backend enables
// the ones selected here; anything unchecked stays off).
const optInModules = [
    { name: 'Meals', label: 'Meals', desc: 'Plan meals and build shopping lists.', icon: 'fas fa-utensils' },
    { name: 'Housing', label: 'Household', desc: 'Chores and household tasks.', icon: 'fas fa-house' },
    { name: 'Profiles', label: 'Profiles', desc: 'A profile for each person in your family.', icon: 'fas fa-users' },
];

const isOn = (name: string): boolean => formData.modules.includes(name);

const toggleModule = (name: string): void => {
    const i = formData.modules.indexOf(name);
    if (i === -1) {
        formData.modules.push(name);
    } else {
        formData.modules.splice(i, 1);
    }
};

const createBudget = () => {
    formData.transform((data) => {
        data.primary_currency_code = data.primary_currency_code.code || data.primary_currency_code
        const parsed = parseTeamForm(data)
        parsed.language = data.language
        parsed.modules = data.modules
        return parsed
    }).post('/onboarding')
}
</script>


<template>
    <AppLayout :is-onboarding="true">
        <div class="h-auto py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between py-2 mb-10 border-4 border-base rounded-md bg-base-lvl-2"
            >
                <div class="px-5 font-bold text-body-1">
                    {{ $t('Space Setup') }}
                </div>

                <div  class="flex overflow-hidden font-bold text-body-1 rounded-lg max-w-min">
                    <LogerButton
                        :disabled="!formData.name"
                        v-if="state.currentMode == 'createTeam'"
                        @click="createBudget"
                        :processing="formData.processing"
                    >
                        {{ $t('Create Space') }}
                    </LogerButton>
                </div>
            </div>

            <TeamForm
                class="w-full px-5 py-4 space-y-5 bg-base-lvl-3 rounded-md "
                v-if="state.currentMode == 'createTeam'"
                :form-data="formData"
            >
                <template #append>
                    <div class="w-full text-right">
                        <button
                            @click="state.currentMode = 'invite'"
                            class="font-bold underline text-primary/80 hover:text-primary"
                        >
                            {{ $t('I have got an invitation') }}
                        </button>
                    </div>
                </template>
            </TeamForm>

            <!-- What do you want to organize? Finance is the door (always on);
                 the rest are opt-in here so the family-OS pillars are discoverable
                 at setup instead of hidden behind a buried settings toggle. -->
            <section
                v-if="state.currentMode == 'createTeam'"
                class="w-full px-5 py-4 mt-5 space-y-3 bg-base-lvl-3 rounded-md"
            >
                <div>
                    <h3 class="font-bold text-body-1">{{ $t('What do you want to organize?') }}</h3>
                    <p class="text-xs text-body-1/60">{{ $t('Turn these on now, or anytime in settings.') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div class="flex items-start gap-3 p-3 border rounded-lg border-primary/40 bg-primary/5">
                        <i class="mt-0.5 fas fa-wallet text-primary" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-body-1">{{ $t('Finances') }}</p>
                            <p class="text-xs text-body-1/60">{{ $t('Budgets, accounts and net worth.') }}</p>
                        </div>
                        <span class="text-[10px] font-semibold text-primary uppercase whitespace-nowrap">{{ $t('Always on') }}</span>
                    </div>

                    <button
                        type="button"
                        v-for="m in optInModules"
                        :key="m.name"
                        @click="toggleModule(m.name)"
                        class="flex items-start gap-3 p-3 text-left transition-colors border rounded-lg"
                        :class="isOn(m.name) ? 'border-primary bg-primary/5' : 'border-base-lvl-2 hover:border-primary/40'"
                    >
                        <i :class="[m.icon, isOn(m.name) ? 'text-primary' : 'text-body-1/40']" class="mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-body-1">{{ $t(m.label) }}</p>
                            <p class="text-xs text-body-1/60">{{ $t(m.desc) }}</p>
                        </div>
                        <span
                            class="flex items-center justify-center w-5 h-5 border rounded shrink-0"
                            :class="isOn(m.name) ? 'bg-primary border-primary text-white' : 'border-base-lvl-2'"
                        >
                            <i v-if="isOn(m.name)" class="text-[10px] fas fa-check" />
                        </span>
                    </button>
                </div>
            </section>

            <AcceptInvitation v-else @change="state.currentMode = 'createTeam'" :invitations="invitations" />
        </div>
    </AppLayout>
</template>
