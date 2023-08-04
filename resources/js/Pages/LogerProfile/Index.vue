<script lang="ts" setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
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
            <ProfileSectionNav :loger-profile="profiles">
                  <template #actions>
                      <div>
                        <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen">
                            Add Loger profile
                        </LogerButton>
                      </div>
                  </template>
            </ProfileSectionNav>

            <main>
                <LogerProfileModal
                    v-if="isModalOpen"
                    v-model:show="isModalOpen"
                    :form-data="resourceToEdit"
                    @saved="onSaved"
                />
            </main>
      </template>
    </AppLayout>
</template>
