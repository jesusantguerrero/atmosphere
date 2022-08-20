<template>
    <jet-form-section @submitted="createTeam">
        <template #title>
            Team Details
        </template>

        <template #description>
            Create a new team to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <jet-label value="Team Owner" />

                <div class="flex items-center mt-2">
                    <img class="w-12 h-12 rounded-full object-cover" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name">

                    <div class="ml-4 leading-tight">
                        <div>{{ $page.props.user.name }}</div>
                        <div class="text-gray-700 text-sm">{{ $page.props.user.email }}</div>
                    </div>
                </div>
            </div>

            <TeamForm
                :timezones="timezones"
                :currencies="currencies"
                :formDate="form"
            />
        </template>

        <template #actions>
            <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Create
            </jet-button>
        </template>
    </jet-form-section>
</template>

<script setup>
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import { Inertia } from '@inertiajs/inertia'
    import TeamForm from '../Onboarding/TeamForm.vue'

    const form = Inertia.form({
        name: '',
    });

    function createTeam() {
        form.post(route('teams.store'), {
            errorBag: 'createTeam',
            preserveScroll: true
        });
    }
</script>
