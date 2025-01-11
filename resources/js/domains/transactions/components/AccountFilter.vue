<script setup lang="ts">
import { inject, computed, h } from "vue";

import { NSelect, SelectRenderLabel } from "naive-ui";
import { IAccount } from "@/domains/transactions/models";
import { formatMoney } from "@/utils";
import MdiCreditCard from '~icons/mdi/credit-card'
import MdiWallet from '~icons/mdi/wallet'

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

const renderLabel: SelectRenderLabel = (option: IAccount) => {
      return h('div',{class: 'w-full flex justify-between space-x-4'},
        [
          h(option.credit_limit > 0 ? MdiCreditCard : MdiWallet),
          h('span', option.label),
          h('div', formatMoney(option.balance))
        ]
      )
    }

</script>

<template>
    <section class="w-80">
        <NSelect
            filterable
            clearable
            tag
            size="large"
            class="w-full"
            placeholder="Filter account"
            :multiple="multiple"
            v-model:value="selectedAccount"
            :default-expand-all="true"
            :render-label="renderLabel"
            :options="accountsOptions"
        />
    </section>
</template>
