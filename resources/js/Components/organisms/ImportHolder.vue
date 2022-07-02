<template>
  <AtField label="Import Transactions">
    <input type="file" />
    <button class="text-slate-200" @click="handleImport">Import</button>
  </AtField>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia";
import { AtField } from "atmosphere-ui";

const handleImport = async () => {
  const file = document.querySelector('input[type="file"]').files[0];
  Inertia.post("/financial/import", {
    file,
  });
};
</script>
