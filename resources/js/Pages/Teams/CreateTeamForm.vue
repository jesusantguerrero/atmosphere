<script setup lang="ts">
    import { router } from '@inertiajs/vue3'
    import { AtField } from "atmosphere-ui";

    import JetButton from '@/Components/atoms/Button.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetInput from '@/Components/atoms/Input.vue'
    import JetInputError from '@/Components/atoms/InputError.vue'
    import JetLabel from '@/Components/atoms/Label.vue'
    import TeamForm from '../Onboarding/TeamForm.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import ProfilePicture from './ProfilePicture.vue'

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
                    :photoUrl="$page.props.user.profile_photo_url"
                    :userName="$page.props.user.name"
                    :email="$page.props.user.email"
                />
            </AtField>

            <TeamForm
                :timezones="timezones"
                :currencies="currencies"
                :formData="form"
            />
        </template>

        <template #actions>
            <LogerButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Create
            </LogerButton>
        </template>
    </JetFormSection>
</template>
