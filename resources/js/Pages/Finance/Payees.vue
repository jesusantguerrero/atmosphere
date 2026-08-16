<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "@/Pages/Finance/Partials/FinanceSectionNav.vue";

interface Payee { id: number; name: string; transactions_count: number; }
const props = defineProps<{ payees: Payee[] }>();
const { t } = useI18n();

const search = ref("");
const filtered = computed(() =>
    props.payees.filter((p) => p.name.toLowerCase().includes(search.value.toLowerCase())));

// rename
const renaming = ref<Payee | null>(null);
const renameValue = ref("");
const openRename = (p: Payee) => { renaming.value = p; renameValue.value = p.name; };
const submitRename = () => {
    if (!renaming.value || !renameValue.value.trim()) return;
    router.patch(`/finance/payees/${renaming.value.id}`, { name: renameValue.value.trim() }, {
        preserveScroll: true, onSuccess: () => { renaming.value = null; },
    });
};

// merge
const merging = ref<Payee | null>(null);
const mergeTargetId = ref<number | null>(null);
const mergeTargets = computed(() => props.payees.filter((p) => p.id !== merging.value?.id));
const openMerge = (p: Payee) => { merging.value = p; mergeTargetId.value = null; };
const submitMerge = () => {
    if (!merging.value || !mergeTargetId.value) return;
    router.post(`/finance/payees/${merging.value.id}/merge`, { target_id: mergeTargetId.value }, {
        preserveScroll: true, onSuccess: () => { merging.value = null; },
    });
};

// delete
const remove = (p: Payee) => {
    const msg = p.transactions_count > 0
        ? t('Delete "{name}"? Its {count} transactions keep their data but lose the payee.', { name: p.name, count: p.transactions_count })
        : t('Delete "{name}"?', { name: p.name });
    if (!window.confirm(msg)) return;
    router.delete(`/finance/payees/${p.id}`, { preserveScroll: true });
};
</script>

<template>
  <AppLayout :title="$t('Payees')">
    <template #header><FinanceSectionNav /></template>
    <main class="px-4 mx-auto mt-6 mb-10 max-w-4xl sm:px-6 lg:px-8">
      <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-4">
        <div>
          <h1 class="text-2xl font-bold text-body">{{ $t('Payees') }}</h1>
          <p class="text-sm text-body-1/70">{{ $t('Rename, merge duplicates, or remove payees.') }}</p>
        </div>
        <input v-model="search" type="text" :placeholder="$t('Search payees')"
               class="w-full sm:w-64 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none focus:border-primary" />
      </header>

      <div class="bg-base-lvl-3 border border-base rounded-xl divide-y divide-base overflow-hidden">
        <div v-for="p in filtered" :key="p.id" class="flex items-center gap-2 px-4 py-3">
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-body truncate">{{ p.name }}</div>
            <div class="text-xs text-body-1/50">{{ p.transactions_count }} {{ $t('transactions') }}</div>
          </div>
          <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg text-body-1/70 hover:text-body hover:bg-base-lvl-2" @click="openRename(p)">{{ $t('Rename') }}</button>
          <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg text-body-1/70 hover:text-body hover:bg-base-lvl-2" @click="openMerge(p)">{{ $t('Merge') }}</button>
          <button class="text-xs font-semibold px-2.5 py-1.5 rounded-lg text-error hover:bg-error/10" @click="remove(p)">{{ $t('Delete') }}</button>
        </div>
        <p v-if="!filtered.length" class="text-center text-sm text-body-1/50 py-10">{{ $t('No payees found') }}</p>
      </div>
    </main>

    <Teleport to="body">
      <div v-if="renaming" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="renaming = null">
        <div class="w-full max-w-sm bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-5">
          <h3 class="font-bold text-body mb-3">{{ $t('Rename payee') }}</h3>
          <input v-model="renameValue" type="text" @keyup.enter="submitRename"
                 class="w-full mb-4 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none focus:border-primary" />
          <div class="flex justify-end gap-2">
            <button class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2" @click="renaming = null">{{ $t('Cancel') }}</button>
            <button class="text-sm font-semibold bg-primary text-white px-4 py-2 rounded-lg disabled:opacity-50" :disabled="!renameValue.trim()" @click="submitRename">{{ $t('Save') }}</button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="merging" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="merging = null">
        <div class="w-full max-w-sm bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-5">
          <h3 class="font-bold text-body mb-1">{{ $t('Merge payee') }}</h3>
          <p class="text-xs text-body-1/60 mb-3">{{ $t('Move all transactions from this payee into another, then delete it.') }}</p>
          <label class="block text-[10px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Merge into') }}</label>
          <select v-model="mergeTargetId" class="w-full mb-4 px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none">
            <option :value="null" disabled>{{ $t('Select a payee') }}…</option>
            <option v-for="tg in mergeTargets" :key="tg.id" :value="tg.id">{{ tg.name }}</option>
          </select>
          <div class="flex justify-end gap-2">
            <button class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2" @click="merging = null">{{ $t('Cancel') }}</button>
            <button class="text-sm font-semibold bg-primary text-white px-4 py-2 rounded-lg disabled:opacity-50" :disabled="!mergeTargetId" @click="submitMerge">{{ $t('Merge') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
