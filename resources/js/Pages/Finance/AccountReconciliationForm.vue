<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { format } from "date-fns";
import { NDatePicker } from "naive-ui";
import { AtField, } from "atmosphere-ui";


import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import { IAccount } from "@/domains/transactions/models";
import { formatMoney } from "@/utils";

const emit = defineEmits(['close']);
const props = withDefaults(defineProps<{
    isVisible: boolean;
    account: IAccount,
}>(), {});

// reconciliation
const reconcileForm = useForm({
    isVisible: false,
    date: new Date(),
    balance: 0,
    hasDifference: false,
})

const onClose = () => {
    reconcileForm.reset()
    emit('close')
}

const reconciliation = () => {
    reconcileForm.transform(data => ({
        ...data,
        date: format(data.date, 'yyyy-MM-dd'),
    })).post(`/finance/reconciliation/accounts/${props.account.id}`, {
        preserveScroll: true,
        only: ['transactions', 'accounts', 'stats'],
        onFinish() {
            onClose()
        }
    });
};

const doQuickReconciliation = () => {
    reconcileForm.balance = props.account.balance;
    reconciliation()
}


</script>

<template>
<ConfirmationModal
    :show="isVisible"
    @close="onClose"
    :max-width="reconcileForm.hasDifference ? 'md' : 'sm'"
    title="Ending statement balance"
>

    <template #content>
        <article v-if="!reconcileForm.hasDifference">
            <h4>Is your current account balance</h4>
            <h2 class="text-lg"> {{ formatMoney(account.balance) }} </h2>
            <footer class="flex justify-end">
                <LogerButton @click="reconcileForm.hasDifference = true" variant="neutral">
                    No
                </LogerButton>
    
                <LogerButton
                    class="ml-2"
                    @click="doQuickReconciliation"
                    :class="{ 'opacity-25': reconcileForm.processing }"
                    :disabled="reconcileForm.processing"
                >
                    Yes
                </LogerButton>
            </footer>
        </article>
        <section v-else>
            <h4 class="font-bold">
            {{ account.name }}
            </h4>
            <AtField
            label="Ending balance Date"
            class="flex justify-between w-full md:block"
        >
            
            <NDatePicker
                v-model:value="reconcileForm.date"
                type="date"
                size="large"
                class="w-full"
            />
        </AtField>

        <AtField label="statement balance">
            <LogerInput
                ref="input"
                class="opacity-100 cursor-text"
                v-model="reconcileForm.balance"
                :number-format="true"

            >
                <template #prefix>
                    {{ account.currency_code }}
                </template>
            </LogerInput>
        </AtField>
        </section>

    </template>

    <template #footer v-if="reconcileForm.hasDifference">
        <section class="flex justify-between">
            <LogerButton @click="onClose" variant="neutral">
                Cancel
            </LogerButton>

            <LogerButton
                class="ml-2"
                @click="reconciliation"
                :class="{ 'opacity-25': reconcileForm.processing }"
                :disabled="reconcileForm.processing"
            >
                Save
            </LogerButton>
        </section>
    </template>
</ConfirmationModal>

</template>
