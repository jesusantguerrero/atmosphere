<script setup lang="ts">
    import { ref } from 'vue'
    import { useForm } from '@inertiajs/vue3'

    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import JetDialogModal from '@/Components/atoms/DialogModal.vue'
    import JetDangerButton from '@/Components/atoms/DangerButton.vue'
    import JetInput from '@/Components/atoms/Input.vue'
    import JetInputError from '@/Components/atoms/InputError.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'

    const props = defineProps<{
        team: { id: number; users: { id: number; name: string; email: string }[] }
    }>();

    const confirming = ref(false);
    const password = ref<HTMLInputElement | null>(null);

    const form = useForm({
        user_id: props.team.users[0]?.id ?? null,
        password: '',
    });

    function confirm() {
        confirming.value = true;
        setTimeout(() => password.value?.focus(), 250);
    }

    function close() {
        confirming.value = false;
        form.reset('password');
    }

    function transfer() {
        form.put(route('teams.owner.update', props.team.id), {
            errorBag: 'transferTeamOwnership',
            preserveScroll: true,
            onSuccess: () => close(),
            onError: () => password.value?.focus(),
        });
    }
</script>

<template>
    <JetActionSection
        :title="$t('Transfer ownership')"
        :description="$t('Hand this space to another member. You stay as an administrator.')"
    >
        <template #content>
            <div class="max-w-xl text-sm text-body-1/70">
                {{ $t('The new owner can delete the space, manage billing and remove you.') }}
            </div>

            <div class="flex flex-wrap items-center gap-3 mt-5">
                <select
                    v-model="form.user_id"
                    class="rounded-md border-base-level-1 bg-base-lvl-3 text-body-1 text-sm"
                >
                    <option v-for="member in team.users" :key="member.id" :value="member.id">
                        {{ member.name }} ({{ member.email }})
                    </option>
                </select>

                <JetDangerButton @click="confirm">
                    {{ $t('Transfer ownership') }}
                </JetDangerButton>
            </div>

            <JetInputError :message="form.errors.user_id" class="mt-2" />

            <JetDialogModal :show="confirming" @close="close">
                <template #title>
                    {{ $t('Transfer ownership') }}
                </template>

                <template #content>
                    {{ $t('Enter your password to confirm the transfer.') }}

                    <div class="mt-4">
                        <JetInput
                            ref="password"
                            v-model="form.password"
                            type="password"
                            class="block w-3/4 mt-1"
                            :placeholder="$t('Password')"
                            @keyup.enter="transfer"
                        />

                        <JetInputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <JetSecondaryButton @click="close">
                        {{ $t('Cancel') }}
                    </JetSecondaryButton>

                    <JetDangerButton
                        class="ml-2"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="transfer"
                    >
                        {{ $t('Transfer ownership') }}
                    </JetDangerButton>
                </template>
            </JetDialogModal>
        </template>
    </JetActionSection>
</template>
