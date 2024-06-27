<script lang="ts" setup>
import { AtInput, AtTable } from "atmosphere-ui";
import { ElCheckbox } from "element-plus";
import { computed, reactive, toRefs } from "vue"

import cols from "./cols";

const props = defineProps({
    tableData: {
      type: Array,
      default() {
          return []
      }
    },
    availableTaxes: {
      type: Array,
      default() {
          return []
      }
    },
    products: {
        type: Array,
        default() {
            return [];
        }
    },
    resourceUrl: {
      type: String,
      default: "/services?filter[name]=%${query}%&relationships=stock"
    },
    isEditing: {
        type: Boolean,
        default: true
    },
    taxes: {
        type: Array,
        default() {
            return [];
        }
    }
})

const emit = defineEmits(['taxes-updated'])

const state = reactive({
    cleaveOptions: {
      percent: {
        numericOnly: true,
        blocks: [3]
      },
      money: {
        decimal: ".",
        thousands: ",",
        precision: 2,
        masked: false
      }
    },
    services: [],
    isLoading: false,
    rowToAdd: {},
    addMode: false,
    renderedCols: computed(() => {
        return props.isEditing ? cols : cols.filter(col => col.name != 'actions')
    })
})

const handleChange = (value, row) => {
    row.payment=parseFloat(value ? row.amount_due : 0)
}

const { rowToAdd, renderedCols, addMode, cleaveOptions } = toRefs(state)
</script>

<template>
  <div class="w-full">
    <AtTable :cols="renderedCols" :tableData="tableData" :hide-empty-text="true">
      <template v-slot:item="{ scope: { row } }">
        <div class="items-center space-x-2 d-flex">
            <ElCheckbox @change="handleChange($event, row)" />
            <span> {{ row.name }}</span>
        </div>
      </template>


      <template v-slot:payment="{ scope: { row } }" v-if="isEditing">
        <AtInput
            class="rounded-md shadow-none border-body-1/10"
            v-model="row.payment"
            :number-format="true"
        />
      </template>
    </AtTable>
  </div>
</template>



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
