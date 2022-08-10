<template>
    <JetFormSection @submitted="updateTeamName" title="Team Name" description="The team's name and owner information.">
        <template #form>
            <!-- Team Owner Information -->
            <AtField label="Team Owner" class="col-span-6">
                <div class="flex items-center mt-2">
                    <img class="w-12 h-12 rounded-full object-cover" :src="team.owner.profile_photo_url" :alt="team.owner.name">

                    <div class="ml-4 leading-tight">
                        <div>{{ team.owner.name }}</div>
                        <div class="text-bod-1 text-sm">{{ team.owner.email }}</div>
                    </div>
                </div>
            </AtField>

            <!-- Team Name -->
            <AtField
                class="col-span-6 sm:col-span-4"
                label="Team Name"
                field="name"
                :errors="form.errors">
                <LogerInput id="name"
                    type="text"
                    v-model="form.name"
                    :disabled="! permissions.canUpdateTeam"
                />
            </AtField>
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

    const props = defineProps(['team', 'permissions']);

    const form = useForm({
        name: props.team.name,
    })

    function updateTeamName() {
        form.put(route('teams.update', props.team), {
            errorBag: 'updateTeamName',
            preserveScroll: true
        });
    };
</script>
