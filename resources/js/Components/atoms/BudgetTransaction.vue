<template>
<div class="w-full text-right" @click="onAssignBudget()">
    <span
        class="px-5 py-1 cursor-pointer rounded-3xl text-body-1 flex items-center group"
        :class="badgeClass"
        @click="toggle"
        title="Execute transaction"
        v-if="spendingAmount"
    >
        <IconBolt class="group-hover:text-yellow-500" /> {{ spendingAmount }}
    </span>
</div>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover, NSelect } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";

    import LogerInput from "./LogerInput.vue";
    import IconBolt from "../icons/IconBolt.vue";

    import { format, startOfMonth } from "date-fns";
    import { useTransactionModal } from "@/domains/transactions";

    const props = defineProps({
        data: {
            type: Object
        },
        formatter: {
            type: Function,
            default() {
                return (value) => {
                    return value
                }
            }
        },
        category: {
            type: Object,
            required: true
        }
    })

    const theme = {
        good: 'text-success',
        danger: 'text-danger',
        needs: 'text-warning',
        overspend: 'text-warning',
        default: 'text-base-lvl-3'
    }

    const BALANCE_STATUS = {
        overspent: 'overspent',
        available: 'available',
        empty: 'empty'
    }

    const status = computed(() => {
        if (props.category.activity < props.data?.amount) {
            return BALANCE_STATUS.available
        } else if (props.category.activity > props.data?.amount) {
            return BALANCE_STATUS.overspent
        }
    })

    const badgeClass = computed(() => {
        let themeColor = theme.default
        if (status.value == BALANCE_STATUS.available) {
            themeColor = theme.good
        } else if (status.value == BALANCE_STATUS.overspent) {
            themeColor = theme.overspend
        }
        return [themeColor]
    })

    const spendingAmount = computed(() => {
        if (!props.data) return 0;
        return props.data.target_type.includes('spending') ? props.data?.amount : 0
    })
    const form = useForm({
        amount: Math.abs(spendingAmount.value),
        source_category_id: null,
        destination_category_id: null,
    });

    const { openTransferModal } = useTransactionModal()
    const onAssignBudget = () => {
        if (BALANCE_STATUS.available || Number(props.category.budgeted) !== Number(form.amount)) {
            openTransferModal({
                transactionData: {
                    date: new Date(),
                    direction: 'WITHDRAW',
                   'category_id': props.category.id,
                   total: Math.abs(spendingAmount.value)
                }
            })

        }
    }

    const accountOptions = inject('accountsOptions', [])
</script>
