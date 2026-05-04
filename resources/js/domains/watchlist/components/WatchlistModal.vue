<script setup lang="ts">
import { computed, inject, watch, type Ref } from "vue";
import { AtButton, AtField } from "atmosphere-ui";
import { router, useForm } from "@inertiajs/vue3";
import { NSelect } from "naive-ui";

import Modal from "@/Components/atoms/Modal.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import InputMoney from "@/Components/atoms/InputMoney.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";

const props = defineProps<{
  show?: boolean;
  maxWidth?: string;
  closeable?: boolean;
  formData?: Record<string, any> | null;
  focusTarget?: boolean;
}>();

const emit = defineEmits(["update:show"]);

const form = useForm<{
  id: number | null;
  name: string;
  type: string;
  input: any[];
  target: number | null;
}>({
  id: null,
  name: "",
  type: "",
  input: [],
  target: null,
});

// Pre-fill from formData (edit mode or example pre-fill)
watch(
  () => props.formData,
  (data) => {
    if (data) {
      form.id = (data.id as number) ?? null;
      form.name = (data.name as string) ?? "";
      form.type = (data.type as string) ?? "";
      form.input = Array.isArray(data.input) ? data.input : [];
      form.target = (data.target as number) ?? null;
    } else {
      form.reset();
    }
  },
  { immediate: true }
);

const isEditMode = computed(() => Boolean(form.id));

const headerLabel = computed(() => {
  if (isEditMode.value) return "Edit Watchlist";
  return "Create Watchlist";
});

const submitLabel = computed(() => (isEditMode.value ? "Save" : "Create"));

const emitClose = () => {
  emit("update:show", false);
};

const submit = () => {
  if (isEditMode.value) {
    router.put(route("watchlist.update", { watchlist: form.id }), form.data(), {
      onSuccess: emitClose,
      preserveScroll: true,
    });
  } else {
    form.submit("post", route("watchlist.store"), {
      onSuccess: emitClose,
    });
  }
};

interface TypeOption {
  value: string;
  label: string;
  description: string;
  example: string;
  icon: string;
}

const options: TypeOption[] = [
  {
    value: "categories",
    label: "Category",
    description: "Track spending in one or more specific categories.",
    example: "e.g. Restaurants, Cafes",
    icon: "fa fa-shapes",
  },
  {
    value: "groups",
    label: "Category Group",
    description: "Track every transaction under a parent category.",
    example: "e.g. everything in Food & Dining",
    icon: "fa fa-folder-tree",
  },
  {
    value: "payees",
    label: "Payee",
    description: "Track spending at one or more specific places.",
    example: "e.g. Netflix, Spotify, UberEats",
    icon: "fa fa-store",
  },
  {
    value: "tags",
    label: "Tag",
    description: "Track transactions you've labeled with a tag.",
    example: "e.g. vacation, business-trip",
    icon: "fa fa-tag",
  },
];

const pickType = (value: string) => {
  form.type = value;
};

const categoryOptions = inject<any[] | Ref<any[]>>("categoryOptions", []);

// Top-level (parent) categories — used when type=groups
const groupOptions = computed(() => {
  const raw = (Array.isArray(categoryOptions) ? categoryOptions : (categoryOptions as Ref<any[]>).value) ?? [];
  return raw.map((cat: any) => ({
    value: cat.id,
    label: cat.name,
  }));
});

const isType = (typeName: string) => form.type == typeName;
</script>


<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="emitClose"
  >
    <header class="flex items-center px-2 py-2 pl-6 font-bold bg-base-lvl-3">
      <button class="pl-0" @click="form.type = ''" v-if="form.type">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          xmlns:xlink="http://www.w3.org/1999/xlink"
          aria-hidden="true"
          role="img"
          class="iconify iconify--ic"
          width="32"
          height="32"
          preserveAspectRatio="xMidYMid meet"
          viewBox="0 0 24 24"
        >
          <path
            fill="currentColor"
            d="M14.71 6.71a.996.996 0 0 0-1.41 0L8.71 11.3a.996.996 0 0 0 0 1.41l4.59 4.59a.996.996 0 1 0 1.41-1.41L10.83 12l3.88-3.88c.39-.39.38-1.03 0-1.41z"
          ></path>
        </svg>
      </button>
      <span class="py-4">{{ headerLabel }}</span>
    </header>

    <section class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div v-if="!form.type" class="space-y-3">
        <p class="text-sm text-body-1 mb-2">What do you want to track?</p>
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          class="w-full text-left flex items-start gap-4 p-4 border border-base rounded-md hover:border-primary hover:bg-primary/5 transition group"
          @click="pickType(option.value)"
        >
          <span class="w-10 h-10 rounded-full flex items-center justify-center bg-base-lvl-2 text-primary group-hover:bg-primary group-hover:text-white transition shrink-0">
            <i :class="option.icon" />
          </span>
          <span class="min-w-0 flex-1">
            <span class="block font-bold text-body">{{ option.label }}</span>
            <span class="block text-sm text-body-1 mt-0.5">{{ option.description }}</span>
            <span class="block text-xs text-body-1 italic mt-1 opacity-75">{{ option.example }}</span>
          </span>
        </button>
      </div>
      <section v-else ref="importHolderRef">
        <AtField label="Name">
          <LogerInput v-model="form.name" />
        </AtField>

        <AtField label="Payees" v-if="isType('payees')">
          <LogerApiSimpleSelect
            v-model="form.input"
            v-model:label="form.payee_label"
            :multiple="true"
            custom-label="name"
            track-id="id"
            placeholder="Choose payees"
            endpoint="/api/payees"
          />
        </AtField>

        <AtField label="Categories" v-if="isType('categories')">
          <NSelect
            filterable
            clearable
            size="large"
            :multiple="true"
            v-model:value="form.input"
            :default-expand-all="true"
            :options="categoryOptions"
          />
        </AtField>

        <AtField label="Category Groups" v-if="isType('groups')">
          <NSelect
            filterable
            clearable
            size="large"
            :multiple="true"
            v-model:value="form.input"
            :options="groupOptions"
            placeholder="Choose category groups"
          />
          <p class="text-xs text-body-1 mt-1">
            Trackea todas las transacciones cuya categoría pertenece a estos grupos.
          </p>
        </AtField>

        <AtField label="Tags" v-if="isType('tags')">
          <LogerApiSimpleSelect
            v-model="form.input"
            :multiple="true"
            custom-label="name"
            track-id="id"
            placeholder="Choose tags"
            endpoint="/api/labels"
          />
        </AtField>

        <AtField label="Monthly target (opcional)">
          <InputMoney v-model="form.target" :number-format="true">
            <template #prefix><span class="flex items-center pl-2">RD$</span></template>
          </InputMoney>
          <p class="text-xs text-body-1 mt-1">
            Si lo defines, te avisamos cuando el gasto del mes pase de este monto.
          </p>
        </AtField>
      </section>
    </section>

    <footer class="flex justify-end w-full px-6 py-4 space-x-3 text-right bg-base">
      <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
      <LogerButton variant="inverse" @click="submit" :disabled="!form.type">
        {{ submitLabel }}
      </LogerButton>
    </footer>
  </modal>
</template>