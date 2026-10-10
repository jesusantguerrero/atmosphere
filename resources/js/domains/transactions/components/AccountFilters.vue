<script setup lang="ts">
import { inject, computed } from "vue";

import { NSelect } from "naive-ui";
import { IAccount, ICategory } from "@/domains/transactions/models";

const props = withDefaults(defineProps<{
    accounts: IAccount[],
    categories: ICategory[];
    includeLabels: boolean;
    col: boolean;
    tagMaxCount: number;
    hideCategories?: boolean;
}>(), {
    tagMaxCount: 3
});

const categoryOptions = inject("categoryOptions", []);
const accountsOptions = inject("accountsOptions", []);

const emit = defineEmits(['update:accounts', 'update:categories'])

const selectedAccounts = computed({
    get: () => {
        return props.accounts
    },
    set: (value: IAccount) => {
        emit('update:accounts', value)
    }
})
const selectedCategories = computed({
    get: () => {
        return props.categories
    },
    set: (value: ICategory[]) => {
        emit('update:categories', value)
    }
})
</script>

<template>
    <div class="flex gap-2" :class="col ? 'flex-col w-full' : 'flex-row flex-wrap items-center'">
        <section :class="col ? 'w-full' : 'w-44 min-w-[11rem]'">
            <label v-if="includeLabels">Accounts:</label>
            <NSelect
                filterable
                clearable
                tag
                size="large"
                class="w-full"
                :placeholder="$t('Filter accounts')"
                multiple
                v-model:value="selectedAccounts"
                :default-expand-all="true"
                :consistent-menu-width="false"
                :options="accountsOptions"
            />
        </section>
        <section v-if="!hideCategories" :class="col ? 'w-full' : 'w-44 min-w-[11rem]'">
            <label v-if="includeLabels">Categories:</label>
            <NSelect
                filterable
                clearable
                tag
                size="large"
                multiple
                class="w-full"
                :placeholder="$t('Exclude categories')"
                :max-tag-count="tagMaxCount"
                v-model:value="selectedCategories"
                :default-expand-all="true"
                :consistent-menu-width="false"
                :options="categoryOptions"
            />
        </section>
    </div>
</template>
