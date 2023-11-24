<script setup lang="ts">
import { inject, computed } from "vue";

import { NSelect } from "naive-ui";
import { IAccount } from "@/domains/transactions/models";

const props = defineProps<{
    modelValue: IAccount,
    multiple: boolean;
}>();

const accountsOptions = inject("accountsOptions", []);

const emit = defineEmits(['update:model-value'])

const selectedAccount = computed({
    get: () => {
        return props.modelValue
    },
    set: (value: IAccount) => {
        emit('update:model-value', value)
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
            :multiple="multiple"
            v-model:value="selectedAccount"
            :default-expand-all="true"
            :options="accountsOptions"
        />
    </section>
</template>
