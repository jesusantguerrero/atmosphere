<script setup lang="ts">
import { ref, inject, watch, computed } from "vue";
import { AtButton, AtField, AtFieldCheck } from "atmosphere-ui";
import { useForm } from "@inertiajs/vue3";

import Modal from "@/Components/atoms/Modal.vue";
import OccurrencePreview from "./OccurrencePreview.vue";
import LogerButton from "./atoms/LogerButton.vue";
import LogerInput from "./atoms/LogerInput.vue";
import ConditionDescription from '@/Components/templates/ConditionDescription.vue';
import ConditionSelect from '@/Components/templates/ConditionSelect.vue';
import SectionNav from '@/Components/molecules/SectionNav.vue';

const props = defineProps({
  show: {
    default: false,
  },
  maxWidth: {
    default: "2xl",
  },
  closeable: {
    default: true,
  },
  formData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(["update:show", 'saved']);

const form = useForm({
    id: null,
    name: '',
    type: 'transactions',
    conditions: {
        description: [{
            operator: '',
            value: ''
        }],
        category_id: null,
        payee_id: '',
        labels: '',
        account_id: ''
    },
    notify_on_avg: true,
    notify_on_last_count: true,
    is_active: true,
    log: []
});

watch(
    () => props.formData,
    (formData) => {
        Object.keys(form.data()).forEach((field) => {
        if (field == 'date') {
            form[field] = new Date(formData[field])
        } else if (formData && formData[field]) {
            form[field] = formData[field]
        }
    })
}, { deep: true, immediate: true })


const modalTitle = computed(() => {
    return form.id ? `Edit ${props.formData.name}` : 'Create occurrence check';
})

const emitClose = () => {
  emit("update:show", false);
};

const isEditing = computed(() => props.formData && props.formData.id);
const submitLabel = computed(() => isEditing.value ? 'Update' : 'Create');

const submit = () => {
  if (!isEditing.value) {
      form.post(route('occurrence.store'), {
        onSuccess() {
            emitClose();
            emit('saved', form.data())
            form.reset()
        }
      });
  } else {
    update()
  }
};

const update = () => {

    form.put(route('occurrence.update', props.formData), {
            onSuccess() {
                emitClose();
                emit('saved', form.data())
                form.reset()
            },
        }
    )
}

const categoryOptions = inject('categoryOptions', [])

const activeTab = ref('information');
const tabs = [{
        label:'Information',
        value: 'information'
    },
    {
        label: 'Rule',
        value: 'rule'
    },
    {
        label: 'Preview',
        value: 'preview'
    }
];

</script>

<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="emitClose"
  >
    <header class="flex items-center px-6 py-2 font-bold bg-base-lvl-3">
        <span class="py-4">
            {{ modalTitle }}
        </span>
    </header>

    <article class="pt-0 pb-4 bg-base-lvl-3 sm:pt-0 sm:pb-4 text-body">
        <SectionNav v-model="activeTab" :sections="tabs" class="px-4"/>
        <section class="content sm:px-6">
            <section v-if="activeTab == 'information'">
                <AtField label="Name">
                    <LogerInput v-model="form.name" />
                </AtField>

                <div class="flex-col space-y-2">
                    <AtFieldCheck v-model="form.notify_on_avg" label="Notify on AVG" />
                    <AtFieldCheck v-model="form.notify_on_last_count" label="Notify on last count" />
                    <AtFieldCheck v-model="form.is_active" label="Active" />
                </div>
            </section>
            <section class="pt-4 space-y-4" v-if="activeTab == 'rule'">
                <ConditionDescription label="Description" v-model="form.conditions.description" />
                <ConditionSelect
                    label="Categories"
                    v-model="form.conditions.category_id"
                    :options="categoryOptions"
                    multiple
                />
            </section>
            <OccurrencePreview
                v-if="activeTab == 'preview'"
                :occurrence="formData"
                :conditions="form.conditions"
                :logs="form.logs"
            />
        </section>
    </article>

    <footer class="flex justify-end px-6 py-4 space-x-3 text-right bg-base" >
      <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
      <LogerButton variant="inverse" @click="submit" :disabled="!form.name">
        {{ submitLabel }}
      </LogerButton>
    </footer>
  </modal>
</template>


