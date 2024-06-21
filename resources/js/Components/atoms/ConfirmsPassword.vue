<script lang="ts" setup>
    import { ref, nextTick } from "vue";

    import JetButton from './Button.vue'
    import JetDialogModal from './DialogModal.vue'
    import JetInput from './Input.vue'
    import JetInputError from './InputError.vue'
    import JetSecondaryButton from './SecondaryButton.vue'

    import LogerButton from "@/Components/atoms/LogerButton.vue";
    import LogerInput from '@/Components/atoms/LogerInput.vue'


    const emit = defineEmits(['confirmed']);


    const props =  defineProps({
        title: {
            default: 'Confirm Password',
        },
        content: {
            default: 'For your security, please confirm your password to continue.',
        },
        button: {
            default: 'Confirm',
        }
    });


        const confirmingPassword = ref(false);
        const form = ref({
            password: '',
            error: '',
        });


        const password = ref();
        const startConfirmingPassword = () => {
                axios.get(route('password.confirmation')).then(response => {
                    if (response.data.confirmed) {
                        emit('confirmed');
                    } else {
                        confirmingPassword.value = true;

                        setTimeout(() => password.value.focus(), 250)
                    }
                })
        };

        const confirmPassword = () => {
                form.value.processing = true;
                axios.post(route('password.confirm'), {
                    password: form.value.password,
                }).then(() => {
                    form.value.processing = false;
                    closeModal()
                    nextTick(() => emit('confirmed'));
                }).catch(error => {
                    form.value.processing = false;
                    form.value.error = error.response.data.errors.password[0];
                    password.value.focus()
                });
        };

        const closeModal = () => {
            confirmingPassword.value = false
            form.value.password = '';
            form.value.error = '';
        };
</script>

<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <JetDialogModal :show="confirmingPassword" @close="closeModal">
            <template #title>
                {{ title }}
            </template>

            <template #content>
                {{ content }}

                <div class="mt-4">
                    <LogerInput type="password" class="mt-1 block w-3/4" placeholder="Password"
                        ref="password"
                        v-model="form.password"
                        @keyup.enter="confirmPassword"
                    />

                    <JetInputError :message="form.error" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <section class="flex justify-end">
                    <LogerButton @click="closeModal" variant="neutral">
                        Cancel
                    </LogerButton>

                    <LogerButton class="ml-2" @click="confirmPassword" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ button }}
                    </LogerButton>
                </section>
            </template>
        </JetDialogModal>
    </span>
</template>


