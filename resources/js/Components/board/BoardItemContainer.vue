<script lang="ts" setup>
import { ref, onMounted } from "vue";
import { ElMessageBox } from "element-plus";

import ItemContainerTask from "./ItemContainerTask.vue";
import ItemGroupCell from "./ItemGroupCell.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  tasks: {
    type: Array,
    required: true,
  },
  boards: {
    type: Array,
    required: false,
    default() {
      return [];
    },
  },
  allowAdd: {
    type: Boolean,
    default: false,
  },
  hideBoardName: {
    type: Boolean,
  },
  title: {
    type: String,
    default: "Todos",
  },
  tracker: {
    type: Object,
  },
});

const emit = defineEmits(['item-deleted']);

const isLoading = ref(false);

const newTask = ref({
  title: "",
  board: null,
  stage: null,
});

onMounted(async () => {
  if (props.boards && props.boards.length) {
    newTask.value.board = props.boards[0];
    newTask.value.stage = props.boards[0].stages[0];
    await getFieldsData();
  }
});

const getFieldsData = () => {
  const data = props.boards.find((planData) => planData.id == newTask.value.board.id)
    .fields;
  // return axios({
  //     url: "/api/fields",
  //     params: {
  //         "filter[board_id]": newTask.value.board.id
  //     }
  // }).then(({ data }) => {
  newTask.value["fieldsData"] = data;
  // });
};

const addItem = () => {
  const newItem = { ...newTask.value };
  const field = newItem.fieldsData.find((field) => field.name == "status");
  const option = field && field.options.find((option) => option.name == "todo");
  newItem.fields = [
    {
      field_id: field.id,
      field_name: field.name,
      value: option && option.name,
    },
  ];
  newItem.plan_id = newItem.board.id;
  newItem.stage_id = newItem.stage.id;
  delete newItem.fieldsData;
  updateItem(newItem);
  newTask.value.title = "";
};

const updateItem = (item) => {
  const method = item.id ? "PUT" : "POST";
  const param = item.id ? `/${item.id}` : "";
  if (isLoading.value) return;
  isLoading.value = true;

  axios({
    url: `/api/plans/${item.plan_id}/items${param}`,
    method,
    data: item,
  })
    .then(() => {
      router.reload({
        preserveScroll: true,
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
};

const confirmDeleteItem = async (item, reload = true) => {
    const canDelete = await ElMessageBox.confirm(
    "Are you sure you want to delete this task?",
    `Delete ${item.title} task`,
    "Yes, delete");

    if (canDelete) {
        axios({
          url: `/api/plans/${item.plan_id}/items/${item.id}`,
          method: "delete",
        }).then(() => {
          emit("item-deleted", item);
          router.reload({
                preserveScroll: true,
            });
        });
    }
}

const onSaved = (title: string) => {
  newTask.value.title = title;
};
</script>

<template>
  <section class="my-5 item-container section-card committed">
    <header class="text-lg font-bold text-cool-gray-600">
      {{ title }}
    </header>
    <slot> </slot>
    <section class="mb-5 text-gray-600 body space-y-1">
      <ItemGroupCell
        ref="ItemGroupCell"
        v-if="allowAdd"
        class="flex items-center w-full mb-10 bg-base-lvl-3 px-4"
        field-name="title"
        :is-title="true"
        :index="-1"
        :item="newTask"
        :show-controls="false"
        :close-on-blur="false"
        :is-new="true"
        :boards="boards"
        placeholder="Task title"
        @saved="onSaved"
        @keydown.enter="addItem()"
      />
      <div v-if="isLoading" class="w-full text-center text-purple-400">
        Adding new task...
      </div>
      <ItemContainerTask
        v-for="task in tasks"
        class="bg-base-lvl-3 px-4"
        :key="`task-${task.id}`"
        :task="task"
        :tracker="tracker"
        @item-clicked="$emit('item-clicked', task)"
        @item-deleted="confirmDeleteItem($event, true)"
        @update-item="$emit('update-item', $event)"
      />

      <section
        v-if="!tasks || !tasks.length"
        class="font-bold text-center text-gray-400 task-item"
      >
        <slot name="empty"> There's no items to show </slot>
      </section>
    </section>
  </section>
</template>

<style lang="scss" scoped>
.task-item {
  @apply py-4 border-b-2 border-gray-100 flex justify-between;
}

.item-container.section-card .body {
  padding-top: 0;
  padding-bottom: 0;
  min-height: unset;
}
</style>
