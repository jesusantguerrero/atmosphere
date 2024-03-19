<script lang="ts" setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerProfileTemplate from '@/Components/templates/LogerProfileTemplate.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';

import ProfileSectionNav from '@/Components/templates/ProfileSectionNav.vue';

import { ref } from 'vue';
import { useForm, router } from "@inertiajs/vue3";


defineProps<{
    profiles: Record<string, string>[]
}>();


const isModalOpen = ref(false);

const form = useForm({
    name: 'partner'
})
const createRelationshipProfile = () => {
    form.post('/loger-profiles/', {
        preserveScroll: true,
        onSuccess() {
            router.visit("/relationships/partner")
        }
    });
};
</script>


<template>
    <AppLayout title="Relationships Overview">
        <template #header>
            <ProfileSectionNav :loger-profile="profiles" @action="isModalOpen = true" />
        </template>

        <LogerProfileTemplate title="Relationships Overview"  ref="profileTemplateRef">
            <WelcomeCard  message="Relationships Overview">
                <section class="flex flex-col items-center pb-12 mx-auto">
                    <img src="./empty-box.svg" class="opacity-50" />
                    <h4 class="text-lg font-bold text-body-1"> There are not relationships created</h4>
                    <LogerButton variant="inverse" @click="createRelationshipProfile()" class="mt-4">
                        Create relationship
                    </LogerButton>
                </section>
            </WelcomeCard>


        </LogerProfileTemplate>
    </AppLayout>
</template>
