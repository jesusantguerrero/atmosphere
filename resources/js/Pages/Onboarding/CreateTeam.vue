<template>
    <AppLayout>
        <div class="h-auto py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div
                class="flex items-center justify-between py-2 mb-10 border-4 border-white rounded-md bg-gray-50"
            >
                <div class="px-5 font-bold text-gray-600">
                    Organization Setup
                </div>

                <div class="flex overflow-hidden font-bold text-gray-500 rounded-t-lg max-w-min">
                    <at-button @click="createOrganization" class="w-48" type="primary"> Create Organization</at-button>
                </div>
            </div>

            <div
                class="w-full px-5 py-10 space-y-5 bg-white rounded-md "
                v-if="state.currentMode == 'createTeam'"
            >
                <div class="pb-2">
                    <div class="space-y-2 md:max-w-sm">
                        <h2 class="font-bold">Organization Name</h2>
                        <at-input placeholder="Business Name" v-model="formData.business_name" />
                    </div>
                </div>

                <div class="w-full">
                    <div class="md:max-w-sm">
                        <h2 class="my-4 font-bold">Payment Preferences</h2>
                        <div class="">
                            <label class=""> Timezone </label>
                            <div>
                                <n-select
                                    v-model:value="formData.business_timezone"
                                    filterable
                                    :options="utcTimezones"
                                    placeholder="Select"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full text-right">
                    <button
                        @click="state.currentMode = 'invite'"
                        class="font-bold text-blue-500 underline hover:text-blue-400"
                    >
                        I have got an invitation
                    </button>
                </div>
            </div>

            <accept-invitation v-else />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import AcceptInvitation from "./AcceptInvitation";
import { NSelect } from "naive-ui";
import { computed, reactive } from "vue";
import { useForm } from '@inertiajs/inertia-vue3';
import { AtButton, AtInput } from "atmosphere-ui";
import { uniq } from "lodash"

const props = defineProps({
    timezones: {
        type: Array,
        default() {
            return;
        },
    },
});

const state = reactive({
    currentMode: "createTeam",
});

const defaultTimezone = "UTC";

const formData = useForm({
    business_name: '',
    business_timezone: defaultTimezone,
});

const utcTimezones = computed(() => {
    const timezones = props.timezones.reduce((timezones, timezone) => {
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
