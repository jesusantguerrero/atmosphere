<script lang="ts" setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerProfileTemplate from '@/Components/templates/LogerProfileTemplate.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';

import ProfileSectionNav from '@/Components/templates/ProfileSectionNav.vue';
import LogerProfileModal from '@/Components/LogerProfileModal.vue';

import { ref } from 'vue';
import { router } from "@inertiajs/vue3";


defineProps<{
    profiles: Record<string, string>[]
}>();


const isModalOpen = ref(false);
const resourceToEdit = ref({});

const onSaved = () => {
    router.reload()
}
</script>


<template>
    <AppLayout title="Home Projects">
        <template #header>
            <ProfileSectionNav :loger-profile="profiles" @action="isModalOpen = true" />
        </template>

        <LogerProfileTemplate title="Loger Profiles"  ref="profileTemplateRef">
            <WelcomeCard  message="Loger profiles">
                <section class="flex flex-col items-center pb-12 mx-auto">
                    <img src="./empty-box.svg" class="opacity-50" />
                    <h4 class="text-lg font-bold text-body-1"> There are not profiles created</h4>
                    <p class="max-w-lg my-3">
                        Lorem ipsum dolor sit amet consectetur adipiscing elit maecenas, ligula rutrum scelerisque vulputate netus urna dui facilisi, habitant auctor sapien a porta nisi arcu. Imperdiet curabitur accumsan torquent fusce mauris blandit fames mollis primis lacinia felis, interdum p.
                    </p>
                    <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen">
                        Add Loger profile
                    </LogerButton>
                </section>
            </WelcomeCard>
            <LogerProfileModal
                v-if="isModalOpen"
                v-model:show="isModalOpen"
                :form-data="resourceToEdit"
                @saved="onSaved"
            />
        </LogerProfileTemplate>
    </AppLayout>
</template>
