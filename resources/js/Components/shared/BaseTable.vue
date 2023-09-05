<script setup lang="ts">
import { computed, ref, watch } from "vue";
import CustomCell from "@/Components/atoms/customCell";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import { useResponsive } from "@/utils/useResponsive";
import { ElTable, ElTableColumn, TableColumnCtx } from "element-plus";

const { isMobile } = useResponsive();

const props = withDefaults(
  defineProps<{
    selectable?: boolean;
    defaultExpandAll?: boolean;
    showSummary?: boolean;
    summaryMethod: Function;
    cols: any[];
    hiddenCols?: string[];
    isLoading?: boolean;
    tableData: any[];
    config?: Record<string, any>;
    pagination?: Record<string, any>;
    total?: number;
    showPrepend?: boolean;
    showAppend?: boolean;
    emptyText?: string;
    hideEmptyText?: boolean;
    hideHeaders?: boolean;
    responsive?: boolean;
    tableClass?: string;
    layout?: string;
    height?: number
  }>(),
  {
    summaryMethod: (data: { columns: TableColumnCtx<any>[]; data: any[] }) => {
      return () => {};
    },
    tableClass: "md:px-4",
    layout: "table",
  }
);

const emit = defineEmits(["selection-change"]);

const getHeaderClass = (row: Record<string, any>) => {
  return row.headerClass;
};

const parseColumn = (column: Record<string, any> | string) => {
  if (typeof column == "string") {
    return {
      name: column,
      label: column,
    };
  }
  return column;
};

const visibleCols = computed(() => {
  const parsedCols = props.cols.map(parseColumn);
  return props.hiddenCols
    ? parsedCols.filter((col) => !props.hiddenCols?.includes(col.name))
    : parsedCols;
});

const selectedRows = ref<Record<number, boolean>>({});

const toggleSelection = (rows?: number[]) => {
  if (rows) {
    rows.forEach((row) => {
      selectedRows.value[row] = !selectedRows.value[row];
    });
  } else {
    selectedRows.value = {};
  }
};

watch(selectedRows, () => {
  emit(
    "selection-change",
    Object.entries(selectedRows)
      .filter(([_id, isSelected]) => isSelected)
      .map(([rowId, isSelected]) => {
        props.tableData.find((row) => row.id == rowId);
      })
  );
});

defineExpose({
  toggleSelection,
});
</script>

<template>
  <section class="ic-base-table">
    <!-- Header and top pagination -->
    <section
      class="flex items-center justify-between px-4 md:px-0 bg-base-lvl-3"
      :class="{ 'py-4': config?.search }"
    >
      <div class="w-full md:px-4" v-if="config?.search">
        <AppSearch class="w-96" v-model="pagination.search" @search="$emit('search')" />
      </div>
      <ElPagination
        v-if="config?.pagination && pagination"
        class="flex justify-end w-full py-4 pr-4"
        :background="!isMobile"
        @current-change="$emit('paginate', $event)"
        @size-change="$emit('size-change', $event)"
        layout="total,prev, pager, next,sizes"
        :current-page="pagination?.page"
        :page-sizes="[10, 20, 50, 100, 200]"
        :page-size="pagination.limit"
        :total="total"
      />
    </section>

    <!-- Table Body -->
    <section :class="tableClass">
      <div
        class="space-y-2"
        v-if="layout == 'grid' || (responsive && $slots.card && isMobile)"
      >
        <slot name="card" :row="row" v-for="row in tableData" />
      </div>
      <ElTable
        v-else
        class="table-fixed"
        style="width: 100%"
        :default-expand-all="defaultExpandAll"
        :show-summary="showSummary"
        :summary-method="summaryMethod"
        :data="tableData"
        :height="height"
        :header-cell-class-name="getHeaderClass"
        @sort-change="$emit('sort', $event)"
        @row-click="$emit('row-click', $event)"
        @selection-change="$emit('selection-change', $event)"
      >
        <ElTableColumn type="selection" width="55" v-if="selectable" />
        <ElTableColumn type="expand" v-if="$slots.expand">
          <template #default="props">
            <slot name="expand" :row="props.row" />
          </template>
        </ElTableColumn>
        <ElTableColumn
          v-for="col in visibleCols"
          :prop="col.name"
          :key="col.name"
          :label="col.label || col.name"
          cell-class-name="px-2 py-4"
          :label-class-name="col.headerClass"
          :header-align="col.align"
          :class-name="col.class"
          :width="col.width"
          :min-width="col.minWidth"
          :class="[col.headerClass]"
        >
          <template v-slot="scope" v-if="$slots[col.name] || col?.render || col.formula">
            <slot :name="col.name" v-bind:scope="scope" :col="col">
              <CustomCell
                v-if="col.render"
                :class="col.class"
                :col="col"
                :data="scope.row"
              />
            </slot>
          </template>
        </ElTableColumn>
      </ElTable>
    </section>
    <!-- Footer and pagination -->
    <section
      class="flex items-center justify-between py-4 bg-base-lvl-3"
      v-if="config?.pagination && pagination"
    >
      <div class="w-full"></div>
      <div class="flex justify-end w-full">
        <ElPagination
          class="flex justify-end w-full pr-4"
          :background="!isMobile"
          @current-change="$emit('paginate', $event)"
          @size-change="$emit('size-change', $event)"
          :current-page="pagination?.page"
          layout="total,prev, pager, next,sizes"
          :page-sizes="[10, 20, 50, 100, 200]"
          :page-size="pagination.limit"
          :total="total"
        />
      </div>
    </section>
  </section>
</template>

<style lang="scss">
.section-actions {
  display: flex;
  background: white;

  .app-search__container {
    width: 70%;
    margin-right: 15px;
  }

  .action-buttons {
    width: 30%;
    display: flex;

    .btn {
      text-align: center !important;
    }
  }
}
.pagination-container {
  background: white;
  height: 42px;
  width: 100%;
}

.el-table .cell {
  padding: 8px 4px;
}

.el-pagination {
  display: flex;
  align-items: center;

  .btn-next .el-icon,
  .btn-prev .el-icon,
  .el-pager li {
    font-size: 16px;
  }
}

.el-loading-mask {
  z-index: 999;
}
</style>
