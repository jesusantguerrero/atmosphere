<template>
    <JetFormSection @submitted="updateTeamName" title="Team Name" description="The team's name and owner information.">
        <template #form>
            <!-- Team Owner Information -->
            <TeamForm
                class="col-span-6 sm:col-span-4"
                :currencies="[]"
                :timezones="[]"
                :form-data="form"
            />
        </template>

        <template #actions v-if="permissions.canUpdateTeam">
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <AtButton type="primary" :is-loading="form.processing" :disabled="form.processing">
                Save
            </AtButton>
        </template>
    </JetFormSection>
</template>

<script setup>
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import { useForm } from '@inertiajs/inertia-vue3'
    import { AtField, AtButton } from "atmosphere-ui"
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import TeamForm from '../Onboarding/TeamForm.vue'
    import { mapTeamFormServer, parseTeamForm } from "@/domains/app"

    const props = defineProps(['team', 'permissions']);
    const form = useForm(mapTeamFormServer(props.team))

    function updateTeamName() {
        form.transform((data) => {
            data.primary_currency_code = data.primary_currency_code.code || data.primary_currency_code
            return parseTeamForm(data)
        }).put(route('teams.update', props.team), {
            errorBag: 'updateTeamName',
            preserveScroll: true
        });
    };
</script>
