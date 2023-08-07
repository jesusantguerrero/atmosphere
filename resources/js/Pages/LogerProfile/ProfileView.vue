<script lang="ts" setup>

import { ref, onMounted, computed } from 'vue';
import { router } from "@inertiajs/vue3";

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerProfileTemplate from '@/Components/templates/LogerProfileTemplate.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import ProfileSectionNav from '@/Components/templates/ProfileSectionNav.vue';
import LogerProfileModal from '@/Components/LogerProfileModal.vue';
import ProfileEntityModal from '@/Components/ProfileEntityModal.vue';
import OccurrenceCard from '@/Components/Modules/occurrence/OccurrenceCard.vue';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
import TransactionsTable from '@/Components/organisms/TransactionsTable.vue';
import BudgetDetailForm from '@/Components/organisms/BudgetDetailForm.vue';

import { IOccurrenceCheck } from '@/Components/Modules/occurrence/models';
import { ITransaction } from '@/Components/Modules/finance/models/transactions';
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
const budgets = computed(() => {
    return entities.reduce((checks: any, entity: any) => {
        if (entity.entity?.budget) {
            checks.push({
                ...entity.entity.budget,
                category: entity.entity,
            });
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
            <section class="space-y-4">
                <WelcomeCard class="mt-5" :message="profile.name">
                    <section class="flex flex-col items-center mx-auto ">
                        <img :src="profile.image_url" />
                    </section>
    
                    <template #action>
                        <LogerButton
                            class="text-sm text-primary"
                            rounded
                            @click="isProfileEntityModalOpen=true"
                        >
                           <IMdiPlus />
                           <span class="ml-2">
                               Link entity
                           </span>
                        </LogerButton>
                    </template>
                </WelcomeCard>

                <section class="w-full">
                    <p v-if="areEntitiesLoading"> ...loading</p>
                    <section v-else class="w-full">
                        <WidgetTitleCard title="Transaction history" class="w-full">
                            <TransactionsTable
                                class="w-full"
                                table-class="overflow-auto text-sm"
                                :transactions="transactions"
                                :parser="transactionDBToTransaction"
                                @edit="''"
                            />
                        </WidgetTitleCard>
                    </section>
                </section>
            </section>

            <template #panel>
                <p v-if="areEntitiesLoading"> ...loading</p>
                <section v-else class="w-full">
                    <OccurrenceCard :checks="occurrenceChecks" class="mx-auto mt-4" />
                    <BudgetDetailForm
                     v-for="budget in budgets"
                        class="mt-5"
                        full
                        :category="budget.category"
                        :item="budget"
                        :hide-details="true"
                        :editable="false"
                    />
                </section>
              </template>
            <LogerProfileModal
                v-if="isModalOpen"
                v-model:show="isModalOpen"
                :form-data="resourceToEdit"
                @saved="onSaved"
            />
            <ProfileEntityModal
                v-if="isProfileEntityModalOpen"
                :profile-id="profile.id"
                v-model:show="isProfileEntityModalOpen"
                :form-data="profileEntity"
                @saved="onSaved"
            />
        </LogerProfileTemplate>
    </AppLayout>
</template>
