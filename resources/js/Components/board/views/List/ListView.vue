<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch, toRefs } from "vue";
import { NDropdown } from "naive-ui";
import { router } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next";

import ItemGroupCell from "../../ItemGroupCell.vue";
import FieldPopover from "../../FieldPopover.vue";
import ListCellTitle from "./ListCellTitle.vue";
import ListCellHeader from "./ListCellHeader.vue";
import ListRow from "./ListRow.vue";
import ListSummaryRow from "./ListSummaryRow.vue";

import { useSyncScroll } from "@/utils/useSyncScroll";

const props = withDefaults(defineProps<{
  createMode: boolean,
  stage: Object,
  board:  Object,
  selectedItems:  any[],
  items: any[],
  filters: Object,
  resourceName: string
}>(), {
    selectedItems: () => ([]),
    items: () => ([]),
    resourceName: 'tasks'
});

const emit = defineEmits([
    "selected-items-updated",
    "item-deleted",
    "saved",
    "stage-updated",
    "board-deleted",
    "save-order",
    "clear-sort",
    "open-item",
    "sort"
]);

const state = reactive({
  newItem: {},
  newField: {},
  isSelectMode: false,
  isEditMode: false,
  isExpanded: true,
  isLoaded: false,
});

watch(props.selectedItems, () => {
  emit("selected-items-updated", props.selectedItems);
});

const visibleFields = computed(() => {
  return props.board?.fields?.filter((field) => !field.hide) ?? [];
});

const tableSize = computed(() => {
  return visibleFields.value.length;
});

watch(
  tableSize,
  () => {
    const documentRoot = document.documentElement;
    documentRoot.style.setProperty("--board-column-size", tableSize.value);
  },
  {
    deep: true,
    immediate: true,
  }
);

function toggleExpand() {
  state.isExpanded = !state.isExpanded;
}

const input = ref();
function toggleEditMode() {
  state.isEditMode = !state.isEditMode;
  nextTick(() => {
    if (input.value) {
      input.value.focus();
    }
  });
}

function addItem(stage, reload) {
  const lastItemOrder = stage.items ? Math.max(...props.stage.items.map((item) => item.order)) : 0;
  state.newItem.board_id = stage.board_id;
  state.newItem.stage_id = stage.id;
  state.newItem.fields = props.board.fields.map((field) => {
    return {
      field_id: field.id,
      field_name: field.name,
      value: state.newItem[field.name],
    };
  });
  state.newItem.order = lastItemOrder + 1;
  emit("saved", { ...state.newItem }, reload);
  state.newItem = {};
  console.log(state.newItem, "here")
}

function handleSelect() {}

function onFieldAdded() {
  state.newField = {};
  router.reload({ preserveScroll: true });
}

function saveChanges(item, field, value) {
  item[field] = value;
  item.fields = props.board.fields.map((field) => {
    return {
      field_id: field.id,
      field_name: field.name,
      value: item[field.name],
    };
  });
  emit("saved", { ...item });
}

function saveStage(stage) {
  stage.name = input.value?.value;
  state.isEditMode = false;
  emit("stage-updated", { ...stage });
}

function saveReorder() {
  prop.stage.items.forEach(async (item, index) => {
    item.order = index;
    emit("saved", { ...item }, false);
  });
  router.reload({ preserveScroll: true });
}

function handleBoardCommands(command: string) {
  switch (command) {
    case "delete":
      emit("board-deleted");
      break;
    case "edit":
      toggleEditMode();
      break;
    case "selection":
      state.isSelectMode = !state.isSelectMode;
      break;
    default:
      break;
  }
}

function handleCommand(item: Record<string, string>, command: string) {
  switch (command) {
    case "delete":
      emit("item-deleted", item);
      break;
    case "edit":
      emit("open-item", item);
      break;
    default:
      break;
  }
}

const sort = (fieldName: string) => {
  emit("sort", fieldName);
};

const clearSort = (fieldName: string) => {
  emit("clear-sort", fieldName);
};

function handleFilterCommands(fieldName: string, command: string) {
  switch (command) {
    case "clearSort":
      clearSort(fieldName);
      break;
    case "sort":
      sort(fieldName);
      break;
    default:
      emit("save-order", fieldName);
      break;
  }
}

function toggleSelection() {
  props.stage?.items.forEach((item: Record<string, string>) => {
    item["selected"] = props.stage?.selected;
  });
}

const { newItem, newField, isSelectMode, isEditMode, isExpanded, isLoaded } = toRefs(
  state
);

const { syncScroll } = useSyncScroll("left", "ic-scroller-slim");
</script>

<template>
  <div
    class="ic-list"
    :class="{ 'rounded-md bg-gray-200 border border-slate-500': !isExpanded }"
    :data-table-size="tableSize"
  >
    <div class="ic-list__body" :class="{ 'not-expanded': !isExpanded, loaded: isLoaded }">
      <!-- Column title -->
      <div class="ic-list__title">
        <ListCellHeader
          class="item-false__header"
          :visibleFields="[{ name: 'title' }]"
          :filters="filters"
          :is-expanded="isExpanded"
          v-slot:default="{ filterIcons }"
        >
          <div class="flex items-center space-x-2 header-cell">
            <div class="flex items-center">
              <span class="toolbar-buttons" @click="toggleExpand">
                <i :class="[isExpanded ? 'fa fa-chevron-down' : 'fa fa-chevron-right']" />
              </span>

              <div class="hidden item-group__selector" v-if="isExpanded">
                <input
                  type="checkbox"
                  v-model="stage.selected"
                  @change="toggleSelection()"
                />
              </div>

              <span class="font-bold handle" v-if="!isEditMode">
                {{ stage.title || stage.name }}
                {{ isSelectMode ? "(Selection Mode)" : "" }}
              </span>

              <div v-else>
                <input
                  :value="stage.name"
                  type="text"
                  ref="input"
                  @keypress.enter="saveStage(stage)"
                  @blur="saveStage(stage)"
                />
              </div>

              <div class="hidden">
                <i class="mx-2 fa fa-edit" @click="toggleEditMode(true)"></i>
                <NDropdown
                  trigger="click"
                  @select="handleBoardCommands"
                  @click.native.prevent
                  :options="[
                    {
                      key: 'edit',
                      label: 'Edit',
                    },
                    {
                      key: 'delete',
                      label: 'Delete',
                    },
                    {
                      key: 'selection',
                      label: 'Select Mode',
                    },
                  ]"
                >
                  <div
                    class="flex justify-center w-5 h-full py-2 text-center rounded-full hover:bg-gray-200"
                  >
                    <div class="flex items-center justify-center">
                      <i class="fa fa-ellipsis-v"></i>
                    </div>
                  </div>
                </NDropdown>
              </div>
            </div>
            <NDropdown
              trigger="click"
              @select="handleFilterCommands('title', $event)"
              @click.native.prevent
              :options="[
                {
                  key: 'sort',
                  label: 'Sort by Task Name',
                },
                {
                  key: 'clearSort',
                  label: 'Clear sort',
                },
                {
                  key: 'saveOrder',
                  label: 'Save this order',
                },
              ]"
            >
              <div class="px-2 space-x-1 py-1 transition cursor-pointer hover:bg-slate-200">
                <span > {{ items.length }} </span>
                <span class="first-letter:capitalize">{{ resourceName }} <i :class="filterIcons.sort" /></span>
              </div>
            </NDropdown>
          </div>
        </ListCellHeader>

        <div class="grid" v-if="isExpanded">
          <draggable
            v-model="stage.items"
            @end="saveReorder"
            handle=".handle"
            class="w-full"
          >
            <ListCellTitle
              v-for="(item, index) in stage.items"
              class="flex bg-gray-200 border border-white item-false"
              :key="`item-false__title-${item.id}`"
              :item="item"
              :index="index"
              :selected-items="selectedItems"
              :is-select-mode="isSelectMode"
              @selected="handleSelect"
              @saved="saveChanges"
              @command="handleCommand"
            />
          </draggable>
        </div>
      </div>
      <!-- Column title -->

      <div
        class="item-group ic-scroller ic-scroller-slim"
        @scroll="syncScroll"
        :id="`${stage.id}-slim-body`"
      >
        <ListCellHeader
          class="grid py-1 text-left item-group-row"
          :visible-fields="visibleFields"
          :filters="filters"
          @sort="sort"
          @clear-sort="clearSort"
          @field-added="onFieldAdded"
        />

        <!-- items  -->
        <template v-if="isExpanded">
          <ListRow
            v-for="(item, index) in items"
            :key="`item-${index}-${item.id}`"
            :id="`item-${index}-${item.id}`"
            :item="item"
            :row-index="index"
            :visible-fields="visibleFields"
            @saved="saveChanges"
          />
        </template>
        <!-- End of items -->
      </div>

      <!-- column add -->
      <div class="ic-list__add" v-if="isExpanded">
        <ListCellHeader class="item-false__header sticky_header">
          <field-popover :field-data="newField" :board="board" @saved="onFieldAdded">
            <i class="fa fa-plus" slot="reference"></i>
          </field-popover>
        </ListCellHeader>

        <div class="grid">
          <div
            class="item-false"
            v-for="item in stage.items"
            :key="`item-false-${item.id}`"
          ></div>
        </div>
      </div>
      <!-- end of column add -->
    </div>

    <!-- new item  -->
    <div class="ic-list__footer" v-if="isExpanded">
      <div class="grid grid-cols-10 text-left item-line">
        <div class="flex items-center col-span-12 px-0 item-line-cell">
          <ItemGroupCell
            class="flex items-center w-full"
            field-name="title"
            :is-title="true"
            :index="-1"
            :item="newItem"
            :is-new="true"
            @saved="newItem['title'] = $event"
            @keydown.enter="addItem(stage)"
          />
        </div>
      </div>
    </div>
    <!-- End of new item -->

    <!-- summary row  -->
    <ListSummaryRow
      :items="items"
      :stage="stage"
      :visible-fields="visibleFields"
      @scroll="syncScroll"
    />
    <!-- end of summary row -->
  </div>
</template>


<style lang="scss">
.header-cell {
  @apply flex items-center;
  padding-left: 0.25rem;
  height: 34px;
}

.item-line-cell {
  min-height: 35px;
}

.item-group {
  overflow: auto;
}

.ic-list__title,
.item-group,
.ic-list__add {
  position: relative;
}

.ic-list {
  overflow: hidden;
  &__body {
    display: grid;
    grid-template-columns: 1fr 1fr 80px;
    position: relative;

    &.not-expanded {
      @apply bg-gray-200;
      width: 100%;
      display: flex;
    }
  }
}

.item-group-row {
  grid-template-columns: repeat(var(--board-column-size), minmax(180px, 100%));

  &__header {
    @apply text-center;
    height: 34px;
  }
}

.item-checkbox {
  @apply bg-gray-300  flex items-center px-2;

  &.selection {
    @apply bg-primary;
  }
}

.item-false {
  @apply bg-gray-200;
  height: 44px;
  width: 100%;
  border: 2px solid white;

  &__header {
    @apply text-center font-bold;
    height: 34px;
    margin: 4px;
  }
}

.false-header {
  height: 34px;
  margin: 4px;
  display: none;

  &.active {
    display: block;
  }
}

.sticky-active {
  position: absolute;
  left: 0;
  top: 0;
  background: #f8f8f8 !important;
  width: 100%;
  height: 50px;
  z-index: 1000;
  will-change: transform;
  .item-group-row__header {
    height: 50px;
    width: 100%;
    background: #f8f8f8;
    display: flex;
    justify-content: center;
    align-items: center;

    &.header-cell {
      justify-content: left;
    }
  }

  &.item-false__header {
    margin-left: 0;
    display: flex;
    align-items: center;
  }
}

.item-group__selector {
  @apply flex justify-center items-center mr-2;
  width: 30px;
  height: 100%;
}

.ic-list__footer {
  width: calc(100% - 8px);
  margin-left: auto;
}
</style>
