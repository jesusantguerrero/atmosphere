<template>
    <AppLayout :is-onboarding="true">
        <div class="h-auto py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between py-2 mb-10 border-4 border-white rounded-md bg-gray-50"
            >
                <div class="px-5 font-bold text-gray-600">
                    Team Setup
                </div>

                <div  class="flex overflow-hidden font-bold text-gray-500 rounded-lg max-w-min">
                    <AtButton v-if="state.currentMode == 'createTeam'" @click="createOrganization" class="w-48 bg-primary text-white"> Create Team</AtButton>
                </div>
            </div>

            <div
                class="w-full px-5 py-4 space-y-5 bg-white rounded-md "
                v-if="state.currentMode == 'createTeam'"
            >
                <AtField class="space-y-2 md:max-w-sm" label="Team Name">
                    <LogerInput placeholder="Team Name" v-model="formData.business_name" />
                </AtField>

                <div class="w-full">
                    <div class="md:max-w-sm">
                        <h2 class="my-4 font-bold">Payment Preferences</h2>
                        <AtField label="Timezone" >
                            <NSelect
                                v-model:value="formData.business_timezone"
                                filterable
                                :options="utcTimezones"
                                placeholder="Select"
                            />
                        </AtField>
                    </div>
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

const props = defineProps({
    timezones: {
        type: Array,
        default() {
            return;
        },
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
});

const utcTimezones = computed(() => {
    const timezones = props.timezones?.reduce((timezones, timezone) => {
        if (timezone.utc) {
            return [...timezones, ...timezone.utc];
        }
        return timezones
    }, []);

    return uniq(timezones).map(timezone => ({ label: timezone, value: timezone }));

})

const createOrganization = () => {
    formData.post('/onboarding')
}
</script>
