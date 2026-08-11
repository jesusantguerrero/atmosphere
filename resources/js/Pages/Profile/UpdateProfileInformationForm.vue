<script lang="ts" setup>
    import { useForm, router } from '@inertiajs/vue3'
    import { AtField } from "atmosphere-ui"
    import { ref } from "vue";

    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetInputError from '@/Components/atoms/InputError.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'

    const props = defineProps<{
        user: Object
    }>();

    const form = useForm({
        _method: 'PUT',
        name: props.user.name,
        email: props.user.email,
        language: props.user.language ?? 'en',
        photo: null,
    });

    const photo = ref<HTMLInputElement | null>(null);
    const photoPreview = ref(null);

    const updateProfileInformation = () => {
        if (photo.value?.files?.[0]) {
            form.photo = photo.value.files[0]
        }

        form.post(route('user-profile-information.update'), {
            errorBag: 'updateProfileInformation',
            preserveScroll: true,
            onSuccess: () => (clearPhotoFileInput()),
        });
    };

    const selectNewPhoto = () => {
        photo.value.click();
    };

    const updatePhotoPreview = () => {
        const reader = new FileReader();

        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };

        reader.readAsDataURL(photo.value.files[0]);
    };

    const deletePhoto = () => {
        router.delete(route('current-user-photo.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                photoPreview.value = null;
                clearPhotoFileInput();
            },
        });
    };

    const clearPhotoFileInput = () => {
        if (photo?.value?.value) {
            photo.value.value = null;
        }
    };
</script>


<template>
    <JetFormSection
        :title="$t('Profile Information')"
        :description="$t('Update your profile information and email address.')"
        @submitted="updateProfileInformation"
    >
        <template #form>
            <!-- Profile Photo -->
            <div class="col-span-6" v-if="$page.props.jetstream.managesProfilePhotos">
                <input type="file" class="hidden" ref="photo" @change="updatePhotoPreview">

                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="relative w-20 h-20 overflow-hidden transition rounded-full group shrink-0 ring-2 ring-base ring-offset-2 ring-offset-base-lvl-3 hover:ring-primary"
                        @click.prevent="selectNewPhoto"
                        :title="$t('Change photo')"
                    >
                        <img v-if="!photoPreview" :src="user.profile_photo_url" :alt="user.name" class="object-cover w-full h-full">
                        <span
                            v-else
                            class="block w-full h-full bg-center bg-no-repeat bg-cover"
                            :style="`background-image: url('${photoPreview}')`"
                        ></span>
                        <span class="absolute inset-0 flex items-center justify-center text-white transition opacity-0 bg-black/40 group-hover:opacity-100">
                            <i class="fa fa-camera" />
                        </span>
                    </button>

                    <div class="space-y-1">
                        <button type="button" class="block text-sm font-semibold text-primary hover:underline" @click.prevent="selectNewPhoto">
                            {{ $t('Change photo') }}
                        </button>
                        <p class="text-xs text-body-1/50">{{ $t('JPG, PNG or GIF. Recommended 200×200px.') }}</p>
                        <button
                            v-if="user.profile_photo_path"
                            type="button"
                            class="block text-xs text-error hover:underline"
                            @click.prevent="deletePhoto"
                        >
                            {{ $t('Remove photo') }}
                        </button>
                    </div>
                </div>

                <JetInputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <AtField class="col-span-6 sm:col-span-4" :label="$t('Name')" field="name" :errors="form.errors">
                <LogerInput id="name" type="text" v-model="form.name" autocomplete="name" />
            </AtField>

            <!-- Email -->
            <AtField class="col-span-6 sm:col-span-4" :label="$t('Email')" field="email" :errors="form.errors">
                <LogerInput id="email" type="email" v-model="form.email" />
            </AtField>

            <!-- Language -->
            <AtField class="col-span-6 sm:col-span-4" :label="$t('Language')" field="language" :errors="form.errors">
                <select
                    id="language"
                    v-model="form.language"
                    class="w-full px-3 py-2 border rounded-md bg-base-lvl-3 border-base focus:outline-none focus:ring focus:ring-primary"
                >
                    <option value="en">English</option>
                    <option value="es">Español</option>
                </select>
            </AtField>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                {{ $t('Saved') }}
            </JetActionMessage>

            <LogerButton :processing="form.processing">
                {{ $t('Save') }}
            </LogerButton>
        </template>
    </JetFormSection>
</template>
