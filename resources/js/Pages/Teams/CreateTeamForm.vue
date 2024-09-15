<script setup lang="ts">
    import { router } from '@inertiajs/vue3'
    import { AtField } from "atmosphere-ui";

    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import TeamForm from '../Onboarding/TeamForm.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import ProfilePicture from './ProfilePicture.vue'

    defineProps<{
        modules: []
    }>();

    const form = router.form({
        name: '',
    });

    function createTeam() {
        form.post(route('teams.store'), {
            errorBag: 'createTeam',
            preserveScroll: true
        });
    }
</script>


<template>
    <JetFormSection @submitted="createTeam">
        <template #title>
            Space Details
        </template>

        <template #description>
            Create a new team to collaborate with others on projects.
        </template>

        <template #form>
            <AtField label="Space Owner">
                <ProfilePicture
                    :photoUrl="$page.props.auth.user.profile_photo_url"
                    :userName="$page.props.auth.user.name"
                    :email="$page.props.auth.user.email"
                />
            </AtField>

            <TeamForm
                :timezones="timezones"
                :currencies="currencies"
                :formData="form"
                :modules="modules"
            />
        </template>

        <template #actions>
            <LogerButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Create
            </LogerButton>
        </template>
    </JetFormSection>
</template>
