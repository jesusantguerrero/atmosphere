<script lang="ts" setup>
    import { useForm, router } from '@inertiajs/vue3'
    import { AtField } from "atmosphere-ui"
    import { ref } from "vue";

    import JetButton from '@/Components/atoms/Button.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetInput from '@/Components/atoms/Input.vue'
    import JetInputError from '@/Components/atoms/InputError.vue'
    import JetLabel from '@/Components/atoms/Label.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'

    const props = defineProps<{
        user: Object
    }>();

    const form = useForm({
        _method: 'PUT',
        name: props.user.name,
        email: props.user.email,
        photo: null,
    });

    const photoPreview = ref(null);

    const updateProfileInformation = () => {
        if (photo.value) {
            form.photo.value = photo.value.files[0]
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
        title="Profile Information"
        description="Update your account's profile information and email address."
        @submitted="updateProfileInformation"
    >
        <template #form>
            <!-- Profile Photo -->
            <div class="col-span-6 sm:col-span-4" v-if="$page.props.jetstream.managesProfilePhotos">
                <!-- Profile Photo File Input -->
                <input type="file" class="hidden"
                            ref="photo"
                            @change="updatePhotoPreview">

                <jet-label for="photo" value="Photo" />

                <!-- Current Profile Photo -->
                <div class="mt-2" v-show="! photoPreview">
                    <img :src="user.profile_photo_url" :alt="user.name" class="object-cover w-20 h-20 rounded-full">
                </div>

                <!-- New Profile Photo Preview -->
                <div class="mt-2" v-show="photoPreview">
                    <span class="block w-20 h-20 rounded-full"
                          :style="'background-size: cover; background-repeat: no-repeat; background-position: center center; background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <jet-secondary-button class="mt-2 mr-2" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo
                </jet-secondary-button>

                <jet-secondary-button type="button" class="mt-2" @click.prevent="deletePhoto" v-if="user.profile_photo_path">
                    Remove Photo
                </jet-secondary-button>

                <jet-input-error :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <AtField class="col-span-6 sm:col-span-4"
                label="Name"
                field="name"
                :errors="form.errors"
            >
                <LogerInput id="name" type="text"  v-model="form.name" autocomplete="name" />
            </AtField>

            <!-- Email -->
            <AtField
                class="col-span-6 sm:col-span-4"
                label="Email"
                field="email"
                :errors="form.errors"
            >
                <LogerInput id="email" type="email" v-model="form.email" />
            </AtField>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <LogerButton :processing="form.processing">
                Save
            </LogerButton>
        </template>
    </JetFormSection>
</template>

