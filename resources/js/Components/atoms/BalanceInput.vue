<template>
<div class="w-full text-right" title="Money Available">
    <NPopover trigger="manual" placement="bottom"  @update:show="handleUpdateShow" :show="showPopover">
        <template #trigger>
            <span
                class="inline-block px-5 py-1 font-bold cursor-pointer rounded-3xl min-w-max"
                :class="badgeClass"
                @click="toggle"
            >
                {{ formatter(value) }}
            </span>
        </template>
        <div>
            <AtField label="Move">
                <LogerInput v-model="form.amount" />
            </AtField>
            <AtField label="To" v-if="status == BALANCE_STATUS.available">
                <NSelect
                    filterable
                    clearable
                    size="large"
                    v-model:value="form.destination_category_id"
                    :default-expand-all="true"
                    :options="categoryOptions"
                />
            </AtField>
             <AtField label="From" v-else>
                <NSelect
                    filterable
                    clearable
                    size="large"
                    v-model:value="form.source_category_id"
                    :default-expand-all="true"
                    :options="categoryOptions"
                />
            </AtField>
            <div class="flex items-center justify-end space-x-2">
                <AtButton class="text-body-1" @click="clear">Cancel</AtButton>
                <AtButton class="text-white rounded-md bg-success" @click="onAssignBudget()"> Save</AtButton>
            </div>
        </div>
    </NPopover>
</div>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover, NSelect } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";

    import LogerInput from "./LogerInput.vue";
    import { format, startOfMonth } from "date-fns";

    const props = defineProps({
        value: {
            type: Number
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
        good: 'text-success bg-base-lvl-2 hover:bg-base-lvl-3',
        danger: 'text-danger',
        needs: 'text-warning',
        overspend: 'text-warning',
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
                type: 'movement'
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
