<script setup lang="ts">
import { reactive, toRefs, watch, computed, ref } from "vue";
import { AtButton, AtField, AtInput, AtErrorBag, AtButtonGroup, AtFieldCheck } from "atmosphere-ui";
import { useDatePager } from "vueuse-temporals";
import { NSelect, NDropdown, NDatePicker } from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { monthDays, WEEK_DAYS, FREQUENCY_TYPE, generateRandomColor, getDateFromIso, formatMoney } from "@/utils";
import { makeOptions } from "@/utils/naiveui";
import { differenceInCalendarMonths, format, parseISO } from "date-fns";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import IconTarget from "@/Components/icons/IconTarget.vue";
import { budgetFrequencies, isSavingBalance, targetTypes, loanMonthlyPayment } from "@/domains/budget";
import BudgetTargetCard from "./BudgetTargetCard.vue";
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

const { t } = useI18n();

const state = reactive({
  form: useForm({
    category_id: null,
    parent_id: null,
    color: generateRandomColor(),
    name: props.category.name,
    amount: 0,
    assigned: 0,
    target_type: "",
    frequency: "MONTHLY",
    frequency_month_date: null,
    frequency_week_day: null,
    frequency_date: null,
    frequency_interval: 0,
    frequency_interval_unit: 0,
    notify: false,
    principal: 0,
    interest_rate: 0,
    term_months: 0,
    loan_start_date: null,
  }),
  isEditing: false,
  hasTarget: Boolean(props.item),
});

const frequencyUnit = computed(() => {
  const names = {
    MONTHLY: {
      field: "frequency_month_date",
      options: monthDays(),
    },
    WEEKLY: {
      field: "frequency_week_day",
      options: makeOptions(WEEK_DAYS),
    },
  };

  return {
    ...names[state.form.frequency],
  };
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
      url: props.item?.id && `/budgets/${props.category.id}/targets/${props.item.id}`,
    },
    save: {
      method: "post",
      url: `/budgets/${props.category.id}/targets`,
    },
  };
  const endpoint = methods[props.item?.id ? "update" : "save"];
  state.form
    .transform((data) => ({
      ...data,
      frequency_date: data.frequency_date && format(new Date(data.frequency_date), "yyyy-MM-dd"),
      loan_start_date: data.loan_start_date && format(new Date(data.loan_start_date), "yyyy-MM-dd"),
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

const { selectedSpan } = useDatePager({ nextMode: "month" });

const monthInstanceCount = computed(() => {
  return (
    state.form.frequency == FREQUENCY_TYPE.WEEKLY &&
    selectedSpan.value.filter((day) => {
      return (
        format(day, "iiiiii").toLowerCase() ==
        state.form?.frequency_week_day?.toLowerCase()
      );
    })
  );
});

const { form } = toRefs(state);

const options = [
  {
    name: "setAssigned",
    label: "Set Assigned",
  },
  {
    name: "setAvailable",
    label: "Set Available",
  },
  {
    name: "clear",
    label: "Clear",
  },
];

const setAmount = (amount: number) => {
  state.form.amount = amount;
};

// Loan target: the monthly payment is derived from the loan terms, so the user
// never types the amount directly — we compute it and mirror it into `amount`
// (the column the MONTHLY funding logic reads) whenever a term changes.
const loanPayment = computed(() =>
  loanMonthlyPayment(state.form.principal, state.form.interest_rate, state.form.term_months)
);

watch(
  () => [state.form.target_type, state.form.principal, state.form.interest_rate, state.form.term_months],
  (_next, prev) => {
    if (state.form.target_type === "loan") {
      state.form.frequency = "MONTHLY";
      state.form.amount = Math.round(loanPayment.value * 100) / 100;
    } else if (prev && prev[0] === "loan") {
      // Switched away from Loan — clear the auto-computed payment so it is not
      // mistaken for a manually entered amount on the newly chosen target type.
      state.form.amount = 0;
    }
  }
);

const clear = () => {
  form.value.amount = 0;
};

const monthlyContributionHint = computed(() => {
  const { frequency, frequency_date, amount, target_type } = state.form;
  if (frequency !== "DATE" || !frequency_date || !amount) {
    return null;
  }
  const targetDate = frequency_date instanceof Date ? frequency_date : new Date(frequency_date);
  if (isNaN(targetDate.getTime())) {
    return null;
  }
  const monthsRemaining = differenceInCalendarMonths(targetDate, new Date());
  if (monthsRemaining <= 0) {
    return t("Need {amount} / month to reach this goal", { amount: formatMoney(amount) });
  }
  const isSavings = ["saving_balance", "savings_monthly"].includes(target_type);
  const remaining = isSavings ? amount - (props.category?.available ?? 0) : amount;
  const perMonth = Math.max(0, remaining) / monthsRemaining;
  return t("Need {amount} / month to reach this goal", { amount: formatMoney(perMonth) });
});

const handleOptions = (option: string) => {
  switch (option) {
    case "setAssigned":
      setAmount(props.category.budgeted);
      break;
    case "setAvailable":
      setAmount(props.category.available);
      break;
    default:
      clear();
      break;
  }
};
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
      Set target
    </LogerButtonTab>

    <div v-else-if="state.isEditing">
      <AtField label="Target Type">
        <NSelect
          filterable
          clearable
          size="large"
          v-model:value="form.target_type"
          :default-expand-all="true"
          :options="targetTypes"
        />
        <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
      </AtField>

      <AtField v-if="form.target_type !== 'loan'" label="Amount" errors="errors" field="amount">
        <AtInput :number-format="true" v-model="form.amount">
          <template #prefix>
            <span class="flex items-center pl-2"> RD$ </span>
          </template>
          <template #suffix>
            <NDropdown
              trigger="click"
              :options="options"
              key-field="name"
              :on-select="handleOptions"
            >
              <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
            </NDropdown>
          </template>
        </AtInput>
      </AtField>

      <section v-if="form.target_type === 'loan'" class="space-y-2">
        <AtField :label="$t('Loan amount')">
          <AtInput :number-format="true" v-model="form.principal">
            <template #prefix>
              <span class="flex items-center pl-2"> RD$ </span>
            </template>
          </AtInput>
        </AtField>

        <div class="flex gap-2">
          <AtField :label="$t('Annual rate %')" class="w-full">
            <AtInput :number-format="true" v-model="form.interest_rate">
              <template #suffix>
                <span class="flex items-center pr-2"> % </span>
              </template>
            </AtInput>
          </AtField>
          <AtField :label="$t('Term (months)')" class="w-full">
            <AtInput :number-format="true" v-model="form.term_months" />
          </AtField>
        </div>

        <AtField :label="$t('Start date')" class="w-full">
          <NDatePicker type="date" size="large" class="w-full" v-model:value="form.loan_start_date" />
        </AtField>

        <div class="flex items-center justify-between px-3 py-2 rounded-md bg-base-lvl-2">
          <span class="text-sm text-body-1/70">{{ $t('Monthly payment') }}</span>
          <span class="font-bold text-body">RD$ {{ formatMoney(loanPayment) }}</span>
        </div>
      </section>

      <section v-if="form.target_type == 'spending'">
        <AtButtonGroup
          v-model="form.frequency"
          :options="budgetFrequencies"
          class="text-sm"
          selected-class="text-white bg-primary"
        />

        <AtField
          label="Every"
          v-if="['MONTHLY', 'WEEKLY'].includes(form.frequency)"
          :errors="errors"
          :field="frequencyUnit.field"
        >
          <NSelect
            filterable
            clearable
            size="large"
            v-model:value="form[frequencyUnit.field]"
            :default-expand-all="true"
            :options="frequencyUnit.options"
          />

          <div class="flex mt-2 space-x-2">
            <div
              v-for="instance in monthInstanceCount"
              class="w-full h-2 bg-primary"
              :key="instance"
            />
          </div>
        </AtField>
      </section>

      <AtField
            label="Date"
            class="w-full"
            v-if="isSavingBalance(form) || !['MONTHLY', 'WEEKLY'].includes(form.frequency)"
        >
        <NDatePicker
          type="date"
          size="large"
          v-model:value="form.frequency_date"
        />
        <p
          v-if="monthlyContributionHint"
          class="mt-2 text-sm font-medium text-primary"
        >
          {{ monthlyContributionHint }}
        </p>
      </AtField>

      <AtFieldCheck v-model="form.notify" label="Notify on AVG" />

      <div class="flex justify-between mt-4">
        <div class="flex font-bold">
          <at-button class="block h-full text-body-1" @click="onCancel">
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

    <BudgetTargetCard
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

