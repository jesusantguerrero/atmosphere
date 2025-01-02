<script setup lang="ts">
import { reactive, toRefs, watch } from "vue";
import { AtButton, AtField } from "atmosphere-ui";
import { useForm } from "@inertiajs/vue3";
import { parseISO } from "date-fns";

import AccountFilter from "@/domains/transactions/components/AccountFilter.vue";
import BudgetMatchCard from "./BudgetMatchCard.vue";
import IconTarget from "@/Components/icons/IconTarget.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { ICategory } from "@/domains/transactions/models";
import { BudgetTarget } from "../models/budget";
// import IconPicker from '../IconPicker.vue';

const props = defineProps<{
  parentId?: number;
  full?: boolean;
  category: ICategory;
  editable: boolean;
  item: BudgetTarget;
  compact?: boolean;
}>();

const emit = defineEmits(["cancel", "deleted"]);

const state = reactive({
  form: useForm({
    category_id: props.category.id,
    parent_id: props.category.parent_id,
    account_id: null,
    notify: false,
  }),
  isEditing: false,
  hasTarget: Boolean(props.item),
});

watch(
  () => props.category.id,
  () => {
    if (props.item) {
      state.form.reset();
      Object.keys(state.form.data()).forEach((key) => {
          // @ts-ignore
        const fieldValue = props.item[key];

        if (fieldValue?.match?.(/^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$/)) {
            // @ts-ignore
            state.form[key] = parseISO(fieldValue);
        } else if (typeof state.form[key] == 'boolean') {
            state.form[key] = Boolean(fieldValue);
        } else {
            // @ts-ignore
            state.form[key] = fieldValue || "";
        }
      });
      state.hasTarget = true;
      state.form.name = props.category.name
    } else {
      state.hasTarget = false;
      state.form.reset();
    }
  },
  { deep: true, immediate: true }
);

const onSubmit = () => {
  const methods = {
    update: {
      method: "put",
      url: props.item?.id && `/budgets/${props.category.id}/budget-match-accounts/${props.item.id}`,
    },
    save: {
      method: "post",
      url: `/budgets/${props.category.id}/budget-match-accounts`,
    },
  };
  const endpoint = methods[props.item?.id ? "update" : "save"];
  state.form
    .transform((data) => ({
      ...data,
      parent_id: data.parent_id || state.parentId,
    }))
    [endpoint.method](endpoint.url, {
      preserveScroll: true,
      onSuccess() {
        state.isEditing = false;
        state.form.reset()
      },
    });
};



const onCancel = () => {
  state.isEditing = false;
  if (state.hasTarget) {
    emit("cancel");
  }
};

const formComplete = useForm({})
const markAsComplete = (item: BudgetTarget) => {
    formComplete.post(route('budget-target.complete', {
        category: props.category,
        budgetTarget: item
    }))
}

const { form } = toRefs(state);
</script>

<template>
  <section class="pb-4 text-left border-b rounded-md bg-base-lvl-3" v-auto-animate>
    <LogerButtonTab
      @click="state.isEditing = true"
      v-if="!state.isEditing && !state.hasTarget"
      class="flex items-center w-full px-2 py-2 rounded-md bg-body-1/5 text-body-1"
    >
      <span class="mr-2">
        <IconTarget />
      </span>
      Set Account Match
    </LogerButtonTab>

    <div v-else-if="state.isEditing">
      <AtField label="Match with account">
          <AccountFilter
                class="w-full"
                show-all
                v-model="form.account_id"
            />
      </AtField>

      <div class="flex justify-between mt-4">
        <div class="flex font-bold">
          <at-button class="block h-full text-gray-500" @click="onCancel">
            Cancel
          </at-button>
        </div>
        <AtButton
          class="block h-full text-white rounded-md bg-primary"
          @click="onSubmit()"
        >
          Save
        </AtButton>
      </div>
    </div>

    <BudgetMatchCard
      :item="item"
      :category="category"
      :editable="editable"
      :is-processing="formComplete.processing"
      class="w-full"
      v-else-if="!compact"
      @edit="state.isEditing = true"
      @completed="markAsComplete(item)"
    />
  </section>
</template>

