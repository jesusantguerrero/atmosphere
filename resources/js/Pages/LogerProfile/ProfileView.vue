<script lang="ts" setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerProfileTemplate from '@/Components/templates/LogerProfileTemplate.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';

import ProfileSectionNav from '@/Components/templates/ProfileSectionNav.vue';
import LogerProfileModal from '@/Components/LogerProfileModal.vue';

import { ref, onMounted, computed } from 'vue';
import { router } from "@inertiajs/vue3";
import OccurrenceCard from '@/Components/Modules/occurrence/OccurrenceCard.vue';
import { IOccurrenceCheck } from '@/Components/Modules/occurrence/models';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
import { ITransaction } from '@/Components/Modules/finance/models/transactions';
import TransactionsTable from '@/Components/organisms/TransactionsTable.vue';
import { transactionDBToTransaction } from '@/domains/transactions';


const { entities } = defineProps<{
    profiles: Record<string, string>[],
    profile: Record<string, string>,
    entities: Record<string, string>[]
}>();


const isModalOpen = ref(false);
const resourceToEdit = ref({});

const onSaved = () => {
    router.reload()
}

const isProfileEntityModalOpen = ref(false);
const profileEntity = ref({});

const onProfileSaved = () => {
    router.reload()
}

const areEntitiesLoading = ref(true)
function fetchProfileEntities() {
    router.reload({
        only: ['entities'],
        onFinish() {
            areEntitiesLoading.value = false;
        }
    })
}

onMounted(() => {
    fetchProfileEntities()
})

const occurrenceChecks = computed(() => {
    return entities.reduce((checks: IOccurrenceCheck[], entity: any) => {
        if (entity.entity?.last_date) {
            checks.push(entity.entity as IOccurrenceCheck);
        }
        return checks;
    }, []);
})
const transactions = computed(() => {
    return entities.reduce((checks: ITransaction[], entity: any) => {
        if (entity.entity?.transactions && entity.entity) {
            checks.push(...entity.entity.transactions);
        }
        return checks;
    }, []);
})
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
        </template>


        <LogerProfileTemplate title="Loger Profiles"  ref="profileTemplateRef">
            <WelcomeCard class="mt-5" :message="profile.name">
                <section class="flex flex-col items-center mx-auto ">
                    <img :src="profile.image_url" />
                    <section class="w-full">
                        <p v-if="areEntitiesLoading"> ...loading</p>
                        <section v-else class="w-full">
                            <OccurrenceCard :checks="occurrenceChecks" class="mx-auto" />
                            <WidgetTitleCard title="Transaction history" class="w-full">
                                <TransactionsTable
                                    class="w-full"
                                    table-class="overflow-auto text-sm"
                                    :transactions="transactions"
                                    :parser="transactionDBToTransaction"
                                    @edit="''"
                                />

                                <template #action>
                                    <button class="text-primary" @click="''">
                                        <i class="fa fa-plus"></i> Add transaction
                                    </button>
                                </template>
                            </WidgetTitleCard>
                        </section>
                    </section>
                </section>
            </WelcomeCard>
            <LogerProfileModal
                v-if="isModalOpen"
                v-model:show="isModalOpen"
                :form-data="resourceToEdit"
                @saved="onSaved"
            />
            <ProfileEntityModal
                v-if="isProfileEntityModalOpen"
                v-model:show="isProfileEntityModalOpen"
                :form-data="profileEntity"
                @saved="onSaved"
            />
        </LogerProfileTemplate>
    </AppLayout>
</template>
