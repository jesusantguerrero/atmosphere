<script setup lang="ts">
import { inject, computed } from "vue";

import { NSelect } from "naive-ui";
import { IAccount, ICategory } from "@/domains/transactions/models";

const props = defineProps<{
    accounts: IAccount[],
    categories: ICategory[]
}>();

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
        <section class="w-80">
            <NSelect
                filterable
                clearable
                tag
                size="large"
                class="w-full"
                multiple
                v-model:value="selectedAccounts"
                :default-expand-all="true"
                :options="accountsOptions"
            />
        </section>
        <section class="w-full bg-red-500">
            <NSelect
                filterable
                clearable
                tag
                size="large"
                multiple
                class="w-full"
                v-model:value="selectedCategories"
                :default-expand-all="true"
                :options="categoryOptions"
            />
        </section>
</template>
