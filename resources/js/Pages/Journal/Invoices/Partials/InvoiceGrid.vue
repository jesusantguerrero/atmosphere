<script lang="ts" setup>
// @ts-ignore
import { AtInput, AtSimpleSelect } from "atmosphere-ui";
import { computed, reactive, toRefs, onMounted } from "vue";
// @ts-ignore
import IconTrash from "@/Components/icons/IconTrash.vue";
import BaseTable from "@/Components/AtTable.vue";
import cols from "./cols";

const props = defineProps({
  tableData: {
    type: Array,
    default() {
      return [];
    },
  },
  availableTaxes: {
    type: Array,
    default() {
      return [];
    },
  },
  products: {
    type: Array,
    default() {
      return [];
    },
  },
  resourceUrl: {
    type: String,
    default: "/services?filter[name]=%${query}%&relationships=stock",
  },
  isEditing: {
    type: Boolean,
    default: true,
  },
  taxes: {
    type: Array,
    default() {
      return [];
    },
  },
  hiddenCols: {
    type: Array,
    default() {
      return [];
    },
  },
});

const emit = defineEmits(["taxes-updated", "deleted"]);

const state = reactive({
  cleaveOptions: {
    percent: {
      numericOnly: true,
      blocks: [3],
    },
    money: {
      decimal: ".",
      thousands: ",",
      precision: 2,
      masked: false,
    },
  },
  services: [],
  isLoading: false,
  rowToAdd: {},
  addMode: false,
  renderedCols: computed(() => {
    return props.isEditing
      ? cols
      : cols.filter((col) => col.name !== "actions" && col.label);
  }),
});

const addRow = () => {
  const itemTaxes = state.rowToAdd.taxes?.length ? state.rowToAdd.taxes : [];
  if (props.isEditing && (!props.tableData.length || props.tableData.at(-1).concept))
    props.tableData.push({
      product_name: state.rowToAdd?.name,
      concept: state.rowToAdd?.name,
      product_id: state.rowToAdd.id,
      quantity: 1,
      discount: 0,
      price: state.rowToAdd.price?.value || 0,
      amount: 0,
      taxes: [...itemTaxes, { id: "new" }],
    });
};

const removeRow = (index: number, row: any) => {
  const isConfirmed = confirm("Do you want to delete this?");
  if (!isConfirmed) {
    return;
  }
  const deleted = { ...row };
  props.tableData.splice(index, 1);
  emit("deleted", deleted);
};

const setTax = (rowIndex: number, taxIndex: number, taxName: string) => {
  const itemRow: any = props.tableData[rowIndex];
  const tax = props.availableTaxes.find(
    (availableTax: any) => taxName == availableTax.name
  );
  itemRow.taxes[taxIndex] = tax;
  emit("taxes-updated", { rowIndex, taxes: itemRow.taxes });
};

const removeTax = (rowIndex: number, taxIndex: number) => {
  const itemRow: any = props.tableData[rowIndex];
  if (itemRow.taxes.length > 1) {
    const taxes = itemRow.taxes.filter((_tax: any, index: number) => index !== taxIndex);
    emit("taxes-updated", { rowIndex, taxes });
  }
};

onMounted(() => {
  addRow();
});

const { renderedCols, cleaveOptions } = toRefs(state);
</script>

<template>
  <div class="w-full text-sm">
    <slot name="prepend" />
    <BaseTable
      :hidden-cols="hiddenCols"
      :cols="renderedCols"
      :table-data="tableData"
      :hide-empty-text="true"
    >
      <template v-slot:concept="{ scope: { row, col } }">
        <slot name="concept" :row="row" :col="col">
          <div class="py-2 d-flex">
            <AtInput
              name=""
              v-model="row.concept"
              class="border-none form-control hover:border"
              rounded
              v-if="isEditing"
            />
            <span v-if="col?.render && !isEditing" v-html="col?.render(row)" />
            <span v-else> {{ row.concept }}</span>
          </div>
        </slot>
      </template>

      <template v-slot:quantity="{ scope }" v-if="isEditing">
        <AtInput
          name=""
          min="1"
          type="number"
          v-model="scope.row.quantity"
          class="text-right border-none form-control hover:border"
          rounded
        />
      </template>

      <template v-slot:discount="{ scope }" v-if="isEditing">
        <AtInput
          class="text-right form-control"
          type="number"
          max="100"
          min="0"
          rounded
          :options="cleaveOptions.percent"
          v-model="scope.row.discount"
        />
      </template>

      <template v-slot:price="{ scope }" v-if="isEditing">
        <div class="space-y-2">
          <AtInput
            v-model="scope.row.price"
            number-format
            rounded
            class="text-right border-none hover:border"
          />
        </div>
      </template>

      <template v-slot:taxes="{ scope }" v-if="isEditing">
        <div
          class="flex items-center w-full mx-auto"
          v-for="(tax, index) in scope.row.taxes"
          :key="`tax-${index}`"
        >
          <AtSimpleSelect
            :options="availableTaxes"
            v-model="scope.row.taxes[index].tax_id"
            v-model:selected="scope.row.taxes[index]"
            @update:model-value="setTax(scope.$index, index, $event)"
            placeholder="Tax"
            option-template="${name} - ${rate}%"
            label="name"
            key-track="id"
            class="w-full"
          />
          {{ tax.name }}
          <button
            class="h-10 px-2 mt-auto ml-2 transition border focus:outline-none hover:text-gray-900 hover:bg-gray-200"
            @click="removeTax(scope.$index, index)"
          >
            <IconTrash class="w-4 h-4 text-gray-400" />
          </button>
        </div>
      </template>

      <template v-slot:actions="{ scope }" v-if="isEditing">
        <button
          @click="removeRow(scope.$index, scope.row)"
          class="invoice-grid__remove-row"
        >
          <IconTrash class="w-4 h-4 text-gray-400" />
        </button>
      </template>

      <!-- table slots -->
      <template v-slot:append v-if="isEditing">
        <button @click="addRow()" class="invoice-grid__add-row">Add Row</button>
      </template>
    </BaseTable>
  </div>
</template>
>

<style lang="scss">
.el-table,
.el-table__body-wrapper {
  overflow: visible;

  td {
    padding: 0 0 0 0 !important;
  }

  .form-control,
  td {
    border: none;
    background: transparent;
    font-size: 13px;

    &:focus {
      outline: none;
      box-shadow: none;
    }
  }
}
.el-table__append-wrapper {
  overflow: visible;
}
</style>

<style lang="scss" scoped>
.invoice-grid {
  &__add-row {
    width: 100%;
    height: 34px;
    color: dodgerblue;
    background: white;
    border: none;
    font-weight: bolder;
    transition: all ease 0.3s;

    &:hover {
      font-size: 1.1em;
    }

    &:focus {
      outline: none;
    }
  }

  &__remove-row {
    background: transparent;
    color: red;
    border: none;
  }
}

.service-select {
  display: grid;
  grid-template-columns: 95% 5%;
  padding: 12px 10px;
  overflow: visible;
}
</style>
