<script setup lang="ts">
import { ref } from 'vue';
import { NDropdown } from 'naive-ui';
import { router, useForm } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import CustomTable from '@/Components/atoms/CustomTable.vue';
import OccurrenceCheckModal from '@/Components/OccurrenceCheckModal.vue';

import { occurrenceCols as cols } from '@/domains/housing/occurrenceCols';
import { OccurrenceItem } from '@/domains/housing/models';
import { ITransaction } from '@/domains/transactions/models';

defineProps({
    occurrence: {
        type: Array,
        default() {
            return []
        }
    }
})

const isModalOpen = ref(false);
const resourceToEdit = ref({});

const onSaved = () => {
    router.reload()
}

const addInstance = (id: number) => {
    router.post(`/housing/occurrence/${id}/instances`)
}

const removeLastInstance = (id: number) => {
    router.delete(`/housing/occurrence/${id}/instances`)
}

const handleDelete = (resource: OccurrenceItem) => {
    if (confirm(`Are you sure you want to delete this check ${resource.name}?`)) {
        router.delete(`/housing/occurrence/${resource.id}`)
    }
}

const syncForm = useForm({})
const syncAll = () => {
    syncForm.post(`/housing/occurrence/sync-all`)
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
            router.post(`/housing/occurrence/${resource.id}/sync`)
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
</script>

<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div class="flex space-x-2">
                        <LogerButton variant="neutral" class="flex" :href="route('occurrences.export')"  target="_blank" as="a">
                            <IMdiFile  class="mr-2" />
                            Export
                        </LogerButton>
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

      <div class="pt-16 pb-20 pl-6 max-w-screen-2xl">
          <div class="flex flex-col items-center justify-center w-full px-4 py-10 mx-auto mt-4 font-bold rounded-md h-92 bg-base-lvl-3 text-body-1 max-w-7xl">
            <div class="space-y-4">
                <CustomTable
                    :cols="cols"
                    :show-prepend="true"
                    :table-data="occurrence"
                >
                    <template v-slot:actions="{ scope: { row } }">
                        <div class="flex justify-end">
                            <div class="flex h-8 overflow-hidden border rounded-lg border-primary">
                                <Button
                                    class="flex items-center p-4 text-white bg-primary"
                                    @click="addInstance(row.id)">
                                    +
                                </Button>
                                <Button class="flex items-center p-4 text-primary "
                                @click="removeLastInstance(row.id)"
                                >
                                    -
                                </Button>
                            </div>
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
                </CustomTable>
            </div>
          </div>
      </div>

      <OccurrenceCheckModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
        @saved="onSaved"
        @close="resourceToEdit=null"
      />
    </AppLayout>
</template>


