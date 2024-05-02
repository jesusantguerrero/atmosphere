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
import OccurrenceWidget from "@/domains/housing/components/OccurrenceWidget.vue";
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';

import TransactionsTable from '@/domains/transactions/components/TransactionsList.vue';
import BudgetDetailForm from '@/domains/budget/components/BudgetDetailForm.vue';

import { IOccurrenceCheck } from "@/domains/housing/models";
import { ITransaction } from '@/domains/transactions/models';
import { transactionLinesDBToTransaction } from '@/domains/transactions';
import axios from 'axios';
import { formatMoney } from '@/utils';


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

const areEntitiesLoading = ref(true)
function fetchProfileEntities() {
    router.reload({
        only: ['entities'],
        onFinish() {
            areEntitiesLoading.value = false;
        }
    })
}

const transactions = ref<ITransaction[]>([]);
const transactionsTotal = ref(0);
const isLoading = ref(false);
const fetchTransactions = (params = location.toString()) => {
    return axios.get(`${location.pathname}/transactions`).then(({ data }: { data : {
        data: ITransaction[]
    }}) => {
        transactions.value = data.data;
        transactionsTotal.value = data.total;
        isLoading.value = false
    })
}

onMounted(async () => {
    fetchTransactions()
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
                            <template #action>
                                {{  formatMoney(transactionsTotal) }}
                            </template>
                            <TransactionsTable
                                class="w-full"
                                table-class="overflow-auto text-sm"
                                :transactions="transactions"
                                :parser="transactionLinesDBToTransaction"
                                @edit="''"
                            />
                        </WidgetTitleCard>
                    </section>
                </section>
            </section>

            <template #panel>
                <p v-if="areEntitiesLoading"> ...loading</p>
                <section v-else class="w-full ic-scroller-slim mb-96">
                    <OccurrenceWidget :checks="occurrenceChecks" class="mx-auto mt-4 mb-4" />

                    <article class="grid md:grid-cols-2 gap-2">
                        <BudgetDetailForm
                            v-for="budget in budgets"

                            full
                            compact
                            :category="budget.category"
                            :item="budget"
                            :hide-details="true"
                            :editable="false"
                        />
                    </article>
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
