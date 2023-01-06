<template>
    <section
        class="cursor-pointer text-body-1 divide-y-2 border overflow-hidden rounded-md"
        :class="theme.default"
        @click="toggle"
    >
        <NPopover>
            <template #trigger>
                <article class="py-4 flex flex-col justify-center items-center mx-auto" :class="badgeClass">
                    <h4 class="text-lg font-bold "> {{ formatter(value) }} </h4>
                    <small>
                        {{ description }}
                    </small>
        
                    <NPopover v-if="isOverspent" trigger="manual" placement="bottom"  @update:show="handleUpdateShow" :show="showPopover">
                        <template #trigger>
                            <AtButton class="rounded-md bg-black/30 text-white">
                                Fix this
                            </AtButton>
                        </template>
                        <div>
                            <AtField label="From">
                                <NSelect
                                    filterable
                                    clearable
                                    size="large"
                                    v-model:value="form.source_category_id"
                                    :default-expand-all="true"
                                    :options="categoryOptions"
                                />
                            </AtField>
                            <div class="flex space-x-2 justify-end items-center">
                                <AtButton class="text-body-1" @click="clear">Cancel</AtButton>
                                <AtButton class="bg-success text-white rounded-md" @click="onAssignBudget()"> Save</AtButton>
                            </div>
                        </div>
                    </NPopover>
                </article>
            </template>
            <section class="text-center">
                <p>Inflow: Ready to assign transactions in Month: <MoneyPresenter :value="category.activity"/> </p>
                <p>Assigned in month: <MoneyPresenter :value="toAssign.budgeted" /> </p>
            </section>
        </NPopover>

        <article class="grid grid-cols-2 divide-x-2 overflow-hidden">
            <section>
                <slot name="activity" />
            </section>
            <section>
                <slot name="target" />
            </section>
        </article>
    </section>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3";
    import { Inertia } from "@inertiajs/inertia";
    import ExactMath from "exact-math";
    import { computed, inject, ref } from "vue"
    import { NPopover, NSelect } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";
    import { format, startOfMonth } from "date-fns";

    import formatMoney from "@/utils/formatMoney";
import MoneyPresenter from "../molecules/MoneyPresenter.vue";

    const props = defineProps({
        value: {
            type: Number
        },
        formatter: {
            type: Function,
            default: formatMoney
        },
        category: {
            type: Object,
            required: true
        },
        toAssign: {
            type: Object,
            required: true
        }
    })

    const theme = {
        good: 'bg-success/80 text-white',
        danger: 'bg-primary text-white',
        needs: 'bg-warning',
        overspend: 'bg-warning',
        default: 'bg-base-lvl-3'
    }

    const BALANCE_STATUS = {
        overspent: {
            name: 'overspent',
            description: 'You assigned more than you have'
        },
        available: {
            name: 'available',
            description: 'To budget'
        },
        empty: {
            name: 'empty',
            description: 'All money assigned'
        }
    }

    const status = computed(() => {
        if (props.value > 0) {
            return BALANCE_STATUS.available.name
        } else if (props.value < 0) {
            return BALANCE_STATUS.overspent.name
        }
        return BALANCE_STATUS.empty.name
    })

    const isOverspent = computed(() => {
        return status.value == BALANCE_STATUS.overspent.name
    })

    const description = computed(() => {
        return BALANCE_STATUS[status.value].description
    })

    const badgeClass = computed(() => {
        let themeColor = theme.default
        if (status.value === BALANCE_STATUS.available.name) {
            themeColor = theme.good
        } else if (status.value === BALANCE_STATUS.overspent.name) {
            themeColor = theme.danger
        }
        return [themeColor]
    })

    const form = useForm({
        amount: Math.abs(props.value),
        source_category_id: null,
        destination_category_id: null,
    });

    const onAssignBudget = () => {
        if (Number(props.value) !== 0) {
            const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
            const field = status.value == BALANCE_STATUS.available ? 'source_category_id' : 'destination_category_id'
            form.transform(data => ({
                ...data,
                budgeted: Math.abs(props.value),
                [field]: props.category.id,
                'type': 'movement'
            })).post(`/budgets/${props.category.id}/months/${month}`, {
                onSuccess() {
                    Inertia.reload({
                        preserveScroll: true,
                    })
                }
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
