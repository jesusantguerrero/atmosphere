<template>
    <JetFormSection 
        @submitted="updateTeamName" 
        title="Budget space name" 
        description="The budget's information."
    >
        <template #form>
            <!-- Team Owner Information -->
            <TeamForm
                class="col-span-6 sm:col-span-4"
                :currencies="[]"
                :timezones="[]"
                :form-data="form"
            />
        </template>

        <template #actions v-if="permissions?.canUpdateTeam">
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <LogerButton type="primary" :is-loading="form.processing" :disabled="form.processing">
                Save
            </LogerButton>
        </template>
    </JetFormSection>
</template>

<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3'

    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
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
