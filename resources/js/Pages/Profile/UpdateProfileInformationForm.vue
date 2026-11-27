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

    const languages = [
        { value: 'es', label: 'Español' },
        { value: 'en', label: 'English' },
    ];

    const clearPhotoFileInput = () => {
        if (photo?.value?.value) {
            photo.value.value = null;
        }
    };
</script>


<template>
    <JetFormSection @submitted="updateProfileInformation">
        <template #form>
            <div class="flex flex-col col-span-6 gap-6 sm:flex-row">
                <!-- Profile Photo -->
                <div v-if="$page.props.jetstream.managesProfilePhotos" class="flex flex-row items-center gap-4 sm:flex-col sm:items-center sm:w-36 shrink-0">
                    <input type="file" class="hidden" ref="photo" accept="image/*" @change="updatePhotoPreview">

                    <button
                        type="button"
                        class="relative w-24 h-24 overflow-hidden transition rounded-full group shrink-0 ring-2 ring-base ring-offset-2 ring-offset-base-lvl-3 hover:ring-primary focus-visible:ring-primary"
                        :aria-label="$t('Change photo')"
                        @click.prevent="selectNewPhoto"
                    >
                        <img v-if="!photoPreview" :src="user.profile_photo_url" :alt="user.name" class="object-cover w-full h-full">
                        <span
                            v-else
                            class="block w-full h-full bg-center bg-no-repeat bg-cover"
                            :style="`background-image: url('${photoPreview}')`"
                        ></span>
                        <span class="absolute inset-0 flex items-center justify-center text-2xl text-white transition opacity-0 bg-black/40 group-hover:opacity-100">
                            <IMdiCameraOutline />
                        </span>
                    </button>

                    <div class="flex flex-col gap-1 sm:items-center">
                        <button type="button" class="text-sm font-semibold text-primary-ink hover:underline" @click.prevent="selectNewPhoto">
                            {{ $t('Change photo') }}
                        </button>
                        <button
                            v-if="user.profile_photo_path"
                            type="button"
                            class="text-xs text-error hover:underline"
                            @click.prevent="deletePhoto"
                        >
                            {{ $t('Remove photo') }}
                        </button>
                    </div>
                    <JetInputError :message="form.errors.photo" />
                </div>

                <div class="grid flex-1 min-w-0 gap-5 sm:grid-cols-2">
                    <AtField :label="$t('Name')" field="name" :errors="form.errors">
                        <LogerInput id="name" type="text" v-model="form.name" autocomplete="name" />
                    </AtField>

                    <AtField :label="$t('Email')" field="email" :errors="form.errors">
                        <LogerInput id="email" type="email" v-model="form.email" autocomplete="email" />
                    </AtField>

                    <fieldset class="flex flex-col gap-2 sm:col-span-2">
                        <legend class="mb-2 text-sm font-medium text-body">{{ $t('Language') }}</legend>
                        <div class="inline-flex self-start p-1 border rounded-lg border-base bg-base-lvl-2">
                            <label
                                v-for="option in languages"
                                :key="option.value"
                                class="px-4 py-2 text-sm font-semibold rounded-md cursor-pointer min-w-24 text-center focus-within:outline focus-within:outline-2 focus-within:outline-primary"
                                :class="form.language === option.value ? 'bg-base-lvl-3 text-body shadow-sm' : 'text-body-1/70 hover:text-body'"
                            >
                                <input v-model="form.language" type="radio" name="language" :value="option.value" class="sr-only">
                                {{ option.label }}
                            </label>
                        </div>
                        <JetInputError :message="form.errors.language" />
                    </fieldset>
                </div>
            </div>
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
