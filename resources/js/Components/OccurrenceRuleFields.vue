<script setup lang="ts">
import { computed, inject, unref } from 'vue';
import { NSelect } from 'naive-ui';
import LogerApiSimpleSelect from '@/Components/organisms/LogerApiSimpleSelect.vue';
const props = defineProps<{ fields: Record<string, any>; prefix: string }>();
const suppliedCategories = inject('categoryOptions', []);
const suppliedAccounts = inject('accountsOptions', []);
const categoryOptions = computed(() => unref(suppliedCategories));
const accountOptions = computed(() => unref(suppliedAccounts));
</script>
<template>
    <div class="space-y-4">
        <div>
            <h4 class="mb-2 text-sm font-medium">{{ $t('Transaction description') }}</h4>
            <div class="space-y-2">
                <div v-for="(condition, index) in fields.description" :key="index" class="grid grid-cols-[1fr_44px] gap-2 sm:grid-cols-[150px_1fr_44px]">
                    <select v-model="condition.operator" :aria-label="$t('Description match type')" class="col-span-2 min-h-[44px] rounded-lg border border-base bg-base-lvl-2 px-3 sm:col-span-1"><option value="contains">{{ $t('Contains') }}</option><option value="is">{{ $t('Exactly matches') }}</option></select>
                    <input v-model="condition.value" maxlength="255" :aria-label="$t('Description text')" :placeholder="$t('For example: supermarket')" class="min-h-[44px] min-w-0 rounded-lg border border-base bg-base-lvl-2 px-3 focus:border-primary focus:outline-none" />
                    <button type="button" class="min-h-[44px] rounded-lg border border-base hover:text-danger focus-visible:ring-2 focus-visible:ring-primary" :aria-label="$t('Remove description condition')" @click="fields.description.splice(index, 1)">×</button>
                </div>
            </div>
            <button type="button" :disabled="fields.description.length >= 10" class="min-h-[44px] px-2 text-sm font-medium text-primary disabled:opacity-40" @click="fields.description.push({ operator: 'contains', value: '' })">+ {{ $t('Add description condition') }}</button>
        </div>
        <div>
            <label :id="`${prefix}-categories`" class="mb-2 block text-sm font-medium">{{ $t('Categories') }}</label>
            <NSelect v-model:value="fields.category_id" :aria-labelledby="`${prefix}-categories`" multiple filterable clearable size="large" :max="100" :options="categoryOptions" :placeholder="$t('Any category')" />
        </div>
        <div>
            <label :id="`${prefix}-payees`" class="mb-2 block text-sm font-medium">{{ $t('Payees') }}</label>
            <LogerApiSimpleSelect v-model="fields.payee_id" :aria-labelledby="`${prefix}-payees`" multiple :max="100" custom-label="name" track-id="id" endpoint="/api/payees" :placeholder="$t('Search payees')" />
        </div>
        <div>
            <label :id="`${prefix}-accounts`" class="mb-2 block text-sm font-medium">{{ $t('Accounts') }}</label>
            <NSelect v-model:value="fields.account_id" :aria-labelledby="`${prefix}-accounts`" multiple filterable clearable size="large" :max="100" :options="accountOptions" :placeholder="$t('Any account')" />
        </div>
        <p class="text-xs text-body-1/70">{{ $t('Within each list, matching one selected item is enough. Empty fields do not restrict the rule.') }}</p>
    </div>
</template>
