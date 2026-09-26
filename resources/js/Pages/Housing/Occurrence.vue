<script setup lang="ts">
import { ref, computed } from 'vue';
import { NDropdown } from 'naive-ui';
import { router, useForm } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import OccurrenceCard from "@/domains/housing/components/OccurrenceCard.vue";

import { useOccurrenceInstance, OccurrenceAction } from '@/domains/housing/useOccurrenceInstance';
import { IOccurrenceCheck, OccurrenceItem } from '@/domains/housing/models';
import { getDayDiff } from '@/utils';
import { ITransaction } from '@/domains/transactions/models';
import StatusButtons from '@/Components/molecules/StatusButtons.vue';
import { useToggleModal } from '@/domains/app/useToggleModal';

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

const onSaved = () => {
    router.reload()
}

// Overdue view (?overdue=1) — deep-linked from the dashboard's "N overdue
// reminders" card. A reminder is overdue when it's been at least its average
// cadence + 3 days since it last happened, matching the dashboard hero and the
// OccurrenceWidget's red threshold exactly, so the count there and the list
// here always agree. The list isn't paginated (the controller returns the full
// team set), so filtering client-side is safe and shows the complete set.
const isOverdueView = new URLSearchParams(window.location.search).get('overdue') === '1';

const isOverdue = (occurrence: IOccurrenceCheck): boolean => {
    const avg = occurrence.avg_days_passed;
    if (!avg || avg <= 0) return false;
    const days = getDayDiff(occurrence.last_date);
    return typeof days === 'number' && days >= avg + 3;
};

const displayedOccurrences = computed<IOccurrenceCheck[]>(() => {
    const list = (props.occurrences ?? []) as IOccurrenceCheck[];
    return isOverdueView ? list.filter(isOverdue) : list;
});

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

const { openModal } = useToggleModal('occurrence');
const handleEdit = (resource: OccurrenceItem) => {
    openModal({
        isOpen: true,
        data: resource
    });
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
    },
    reminded: {
        name: "reminded",
        label: "Remind",
        handle(resource: OccurrenceItem) {
            router.post(`/housing/occurrences/${resource.id}/remind`)
        }
    }
}

type IOptionNames = keyof typeof defaultOptions;
const options = () => {
    return Object.values(defaultOptions).filter(option => !option.hide)
};

const handleOptions = (optionName: IOptionNames , occurrence: OccurrenceItem) => {
    defaultOptions[optionName].handle(occurrence)
};

const dataStatus = {
  all: {
    label: "All reminders",
    value: "/housing/occurrence",
  },
  overdue: {
    label: "Overdue",
    value: "/housing/occurrence?overdue=1",
  },
  1: {
    label: "Favorites",
    value: "/housing/occurrence?filter[is_liked]=1",
  },
};

const currentStatus = ref(isOverdueView ? "overdue" : (props.serverSearchOptions.filters?.is_liked || "all"));
</script>

<template>
    <AppLayout :title="$t('Reminders')">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div class="flex space-x-2">
                        <StatusButtons
                            v-model="currentStatus"
                            :statuses="dataStatus"
                            @change="router.visit($event)"
                        />
                        <LogerButton variant="secondary"  class="flex" @click="openModal()">
                            <IMdiPlus class="mr-2"/>
                            {{ $t('Add Check') }}
                        </LogerButton>
                        <LogerButton variant="secondary"  class="flex" @click="syncAll()">
                            <IMdiSync class="mr-2" :class="{'animate-spin': syncForm.processing }" />
                            {{ $t('Sync') }}
                        </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <section class="space-y-2 w-full mt-6 mb-20" v-if="displayedOccurrences.length">
                <OccurrenceCard
                    v-for="occurrence in displayedOccurrences"
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
                    <h4 class="text-lg font-bold text-body-1">{{ $t('Reminders') }}</h4>
                    <p class="max-w-lg my-3">
                        {{ $t('Occurrences track the duration of events based on transactions or manual input') }}
                    </p>
                    <LogerButton variant="inverse" @click="openModal()">
                        {{ $t('Add occurrence check') }}
                    </LogerButton>
                </section>
            </WelcomeCard>

        </main>
    </AppLayout>
</template>


