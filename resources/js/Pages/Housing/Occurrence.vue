<script setup lang="ts">
import { ref } from 'vue';
import { NDropdown } from 'naive-ui';
import { router, useForm } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import OccurrenceCheckModal from '@/Components/OccurrenceCheckModal.vue';
import OccurrenceCard from "@/domains/housing/components/OccurrenceCard.vue";

import { useOccurrenceInstance, OccurrenceAction } from '@/domains/housing/useOccurrenceInstance';
import { IOccurrenceCheck, OccurrenceItem } from '@/domains/housing/models';
import { ITransaction } from '@/domains/transactions/models';
import StatusButtons from '@/Components/molecules/StatusButtons.vue';

const props = defineProps({
    occurrences: {
        type: Array,
        default() {
            return []
        }
    },
    serverSearchOptions: {
        type: Object,
        default: () => ({}),
    },
})

const isModalOpen = ref(false);
const resourceToEdit = ref({});

const onSaved = () => {
    router.reload()
}

const { applyChange, remove, isProcessing, isLoading } = useOccurrenceInstance()

const addInstance = (occurrence: IOccurrenceCheck) => {
    applyChange(occurrence, OccurrenceAction.Add)
}

const removeLastInstance = (occurrence: IOccurrenceCheck) => {
    applyChange(occurrence, OccurrenceAction.Delete)
}

const handleDelete = (resource: IOccurrenceCheck) => {
    if (confirm(`Are you sure you want to delete this check ${resource.name}?`)) {
        remove(resource)
    }
}

const syncForm = useForm({})
const syncAll = () => {
    syncForm.post(`/housing/occurrences/sync-all`)
}

const handleEdit = (resource: OccurrenceItem) => {
    resourceToEdit.value = resource;
    isModalOpen.value = true;
}

const defaultOptions = {
    edit: {
        name: "edit",
        label: "Edit",
        handle: handleEdit
    },
    sync: {
        name: 'sync',
        label: 'Sync',
        handle(resource: OccurrenceItem) {
            router.post(`/housing/occurrences/${resource.id}/sync`)
        }
    },
    removed: {
        name: "removed",
        label: "Remove",
        handle: handleDelete
    }
}

type IOptionNames = keyof typeof defaultOptions;
const options = () => {
    return Object.values(defaultOptions).filter(option => !option.hide)
};

const handleOptions = (optionName: IOptionNames , transaction: ITransaction) => {
    defaultOptions[optionName].handle(transaction)
};

const dataStatus = {
  all: {
    label: "All checks",
    value: "/housing/occurrence",
  },
  1: {
    label: "Favorites",
    value: "/housing/occurrence?filter[is_liked]=1",
  },
};

const currentStatus = ref(props.serverSearchOptions.filters?.is_liked || "all");
</script>

<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div class="flex space-x-2">
                        <StatusButtons
                            v-model="currentStatus"
                            :statuses="dataStatus"
                            @change="router.visit($event)"
                        />
                        <LogerButton variant="secondary"  class="flex" @click="isModalOpen = !isModalOpen">
                            <IMdiPlus class="mr-2"/>
                            Add Check
                        </LogerButton>
                        <LogerButton variant="secondary"  class="flex" @click="syncAll()">
                            <IMdiSync class="mr-2" :class="{'animate-spin': syncForm.processing }" />
                            Sync
                        </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <section class="space-y-2 w-full mt-6 mb-20" v-if="occurrences.length">
                <OccurrenceCard
                    v-for="occurrence in occurrences"
                    :occurrence
                    class="w-full border rounded-lg shadow-md cursor-pointer text-body bg-base-lvl-3 hover:bg-base-lvl-2"
                    :is-loading="isLoading(occurrence.id)"
                    :disabled="isProcessing"
                    @add-instance="addInstance(occurrence)"
                    @remove-instance="removeLastInstance(occurrence)"
                >
                <template v-slot:actions="{ scope: row }">
                    <div class="flex justify-end">
                        <NDropdown
                            trigger="click"
                            key-field="name"
                            :options="options(row)"
                            :on-select="(optionName) => handleOptions(optionName, row)"
                            @click.stop
                        >
                            <button class="px-2 hover:bg-base-lvl-3"> <i class="fa fa-ellipsis-v"></i></button>
                        </NDropdown>
                    </div>
                </template>
                </OccurrenceCard>
            </section>

            <WelcomeCard  v-else  class="space-y-2 w-full mt-6 mb-20" borderless>
                <section class="flex flex-col items-center pb-12 mx-auto">
                    <span class=" font-bold text-2xl">
                        <IMdiTimerStarOutline />
                    </span>
                    <h4 class="text-lg font-bold text-body-1"> Occurrence Checks</h4>
                    <p class="max-w-lg my-3">
                        Occurrences track the duration of events based on transactions or manual input
                    </p>
                    <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen">
                        Add occurrence check
                    </LogerButton>
                </section>
            </WelcomeCard>

            <OccurrenceCheckModal
                v-if="isModalOpen"
                v-model:show="isModalOpen"
                :form-data="resourceToEdit"
                @saved="onSaved"
                @close="resourceToEdit=null"
            />
        </main>
    </AppLayout>
</template>


