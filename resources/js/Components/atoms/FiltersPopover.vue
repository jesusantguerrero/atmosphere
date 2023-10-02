<script setup lang="ts">
    import { useForm } from "@inertiajs/vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover } from "naive-ui";

    import { format, startOfMonth } from "date-fns";

    const props = defineProps({
        value: {
            type: Number
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
        }
    })

    const theme = {
        good: 'bg-success',
        danger: 'bg-danger',
        needs: 'bg-warning',
        overspend: 'bg-warning',
        default: 'bg-base-lvl-3'
    }

    const BALANCE_STATUS = {
        overspent: 'overspent',
        available: 'available',
        empty: 'empty'
    }

    const status = computed(() => {
        if (props.value > 0) {
            return BALANCE_STATUS.available
        } else if (props.value < 0) {
            return BALANCE_STATUS.overspent
        }
    })

    const badgeClass = computed(() => {
        let themeColor = theme.default
        if (props.value > 0) {
            themeColor = theme.good
        } else if (props.value < 0) {
            themeColor = theme.overspend
        }
        return [themeColor]
    })

    const form = useForm({
        amount: Math.abs(props.value),
        source_category_id: null,
        destination_category_id: null,
    });

    const onAssignBudget = () => {
        if (BALANCE_STATUS.available || Number(props.category.budgeted) !== Number(form.amount)) {
            const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
            const field = status.value == BALANCE_STATUS.available ? 'source_category_id' : 'destination_category_id'
            form.transform(data => ({
                ...data,
                budgeted: BALANCE_STATUS.available ? data.amount : props.category.budgeted + data.amount,
                [field]: props.category.id,
                type: 'movement',
                date: format(new Date(), 'yyyy-MM-dd')
            })).post(`/budgets/${props.category.id}/months/${month}`, {
                preserveState: true,
                preserveScroll: true
            });
        }
    }

    const categoryOptions = inject('categoryOptions', [])


    const showPopover = ref(false)
    const clear = () => {
        form.reset();
        showPopover.value = false;
    }
    const toggle = () => {
        showPopover.value = !showPopover.value
    }
</script>

<template>
<div class="w-full text-right">
    <NPopover trigger="manual" placement="bottom"  @update:show="handleUpdateShow" :show="showPopover">
        <template #trigger>
            <button
                class="px-5 py-1 cursor-pointer rounded-3xl text-body-1"
            >
                <slot />
            </button>
        </template>
        <div>

        </div>
    </NPopover>
</div>
</template>

