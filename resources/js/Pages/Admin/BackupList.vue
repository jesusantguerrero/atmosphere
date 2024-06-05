<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/core";

// @ts-ignore: its my template
import AtTable from "@/Components/shared/BaseTable.vue";
import AppButton from "@/Components/atoms/LogerButton.vue";

import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import AdminTemplate from "./Partials/AdminTemplate.vue";
import { useForm } from "@inertiajs/vue3";

defineProps<{
  data: Record<string, string>[];
  user: Record<string, string>;
}>();

const tableConfig = {
  selectable: true,
  searchBar: true,
  pagination: true,
};

const searchText = ref();
const reset = () => {};
const executeSearch = () => {};

const backupGenerateForm = useForm({});
const generateBackup = () => {
  if (!backupGenerateForm.processing) backupGenerateForm.post("/admin/backups");
};

const sendBackupForm = useForm({
  fileName: "",
  endpoint: "",
});

type BackupMethod = "send" | "download";
const sendBackupFile = (fileName: string, endpoint: BackupMethod = "send") => {
  const endpoints = {
    send: {
      url: "/admin/send-backup",
      method: "post",
    },
    download: {
      url: "/admin/backups/download",
      method: "get",
    },
  };
  if (!sendBackupForm.processing) sendBackupForm.fileName = fileName;
  const endpointMode = endpoints[endpoint];
  sendBackupForm.endpoint = endpoints[endpoint].url;
  const method = endpointMode.method as string;
  // @ts-ignore;
  sendBackupForm[method](endpointMode.url, {
    onSuccess() {
      sendBackupForm.reset();
    },
  });
};

const isProcessing = (fileName: string, endpoint?: string) => {
  const isSameFile = sendBackupForm.fileName == fileName && sendBackupForm.processing;
  return endpoint ? endpoint == sendBackupForm.endpoint && isSameFile : isSameFile;
};
</script>

<template>
  <AdminTemplate title="Teams">
    <main class="pb-16">
      <section class="flex space-x-4">
        <AppSearch
          v-model.lazy="searchText"
          class="w-full md:flex"
          :has-filters="true"
          @clear="reset()"
          @blur="executeSearch"
        />
        <AppButton
          @click="generateBackup()"
          :disabled="backupGenerateForm.processing"
          :processing="backupGenerateForm.processing"
        >
          Generate Backup
        </AppButton>
      </section>
      <AtTable
        class="bg-white rounded-md text-body-1 mt-4"
        :table-data="data"
        :cols="[
          {
            label: 'Filename',
            name: 'label',
            render(row: string) {
              return row;
            },
          },
          {
            name: 'actions',
            label: 'Action',
          },
        ]"
        @search="() => {}"
        :config="tableConfig"
      >
        <template v-slot:actions="{ scope: { row } }" class="flex">
          <div class="flex justify-end items-center">
            <a
              class="hover:text-primary transition items-center flex justify-center hover:border-primary-400"
              variant="neutral"
              :href="`/admin/backups/download?fileName=${row}`"
              :download="row"
            >
              <IMdiDownload class="mr-2" /> Download
            </a>
            <AppButton
              class="hover:text-primary transition items-center flex justify-center hover:border-primary-400"
              variant="neutral"
              @click="sendBackupFile(row)"
              :disabled="isProcessing(row)"
              :processing="isProcessing(row)"
            >
              <IMdiMail class="mr-2" /> Send
            </AppButton>
            <AppButton
              class="hover:text-error transition items-center flex justify-center hover:border-error ml-2"
              variant="neutral"
              @click="
                router.delete(`/admin/delete-backup`, {
                  data: {
                    fileName: row,
                  },
                })
              "
            >
              <IMdiTrash />
            </AppButton>
          </div>
        </template>
      </AtTable>
    </main>
  </AdminTemplate>
</template>
