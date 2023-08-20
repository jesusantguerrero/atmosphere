<script setup lang="ts">
    import { useForm } from "@inertiajs/vue3";
    import { computed } from "vue"

    import IconBolt from "@/Components/icons/IconBolt.vue";

    import { useTransactionModal } from "@/domains/transactions";
    import formatMoney from "@/utils/formatMoney";

    const props = defineProps({
        data: {
            type: Object
        },
        formatter: {
            type: Function,
            default() {
                return (value: string) => {
                    return value
                }
            }
        },
        category: {
            type: Object,
            required: true
        },
        iconOnly: {
            type: Boolean,
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

    const { openTransactionModal } = useTransactionModal()
    const onAssignBudget = () => {
        if (BALANCE_STATUS.available || Number(props.category.budgeted) !== Number(form.amount)) {
            openTransactionModal({
                transactionData: {
                    direction: 'WITHDRAW',
                   'category_id': props.category.id,
                   total: Math.abs(spendingAmount.value)
                }
            })

        }
    }
</script>

<template>
    <span
        class="flex items-center py-1 cursor-pointer rounded-3xl text-body-1 group"
        :class="[badgeClass, iconOnly ? 'px-2' : 'w-full text-right px-5']"
        @click.stop="onAssignBudget()"
        title="Execute transaction"
        v-if="spendingAmount"
    >
        <IconBolt class="group-hover:text-yellow-500" />
        <span v-if="!iconOnly">
            {{ formatMoney(spendingAmount) }}
        </span>
</span>
</template>