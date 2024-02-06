<script lang="ts" setup>
    import { useForm } from "@inertiajs/vue3";
    import { router } from "@inertiajs/vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover, } from "naive-ui";
    // @ts-ignore
    import { AtField, AtButton } from "atmosphere-ui";
    import { format, startOfMonth } from "date-fns";
    import Multiselect from "vue-multiselect";

    import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
    import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";

    import formatMoney from "@/utils/formatMoney";

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

    const pageState = inject('pageState', {
        dates: {
            endDate: new Date()
        }
    });

    const onAssignBudget = () => {
        if (Number(props.value) !== 0) {
            const month = format(startOfMonth(pageState?.dates?.endDate), 'yyyy-MM-dd');

            const field = status.value == BALANCE_STATUS.available ? 'source_category_id' : 'destination_category_id'
            form.transform(data => ({
                ...data,
                amount: Math.abs(props.value),
                [field]: props.category.id,
                source_category_id: data.source_category_id?.value,
                'type': 'movement',
                date: format(startOfMonth(pageState?.dates?.endDate), 'yyyy-MM-dd')
            })).post(`/budgets/${props.category.id}/months/${month}`, {
                onSuccess() {
                    router.reload({
                        preserveScroll: true,
                    })
                }
            });
        }
    }

    const categories = inject('categories', ref({ data: []}))
    const categoryOptions = computed(() => {
        return categories.value.data?.map(item => ({
            value: item.id,
            key: item.id,
            label: item.name,
            type: 'group',
            children: item.subCategories.map(category => ({
                value: category.id,
                label: category.name,
                available: category.available,
            }))
        }))
    })

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
    <section
        class="overflow-hidden border divide-y-2 rounded-md cursor-pointer text-body-1"
        :class="theme.default"

    >
        <NPopover>
            <template #trigger>
                <article class="relative flex flex-col items-center justify-center mx-auto" :class="badgeClass">
                    <slot name="top" />
                    <header class="flex w-full ">
                        <section class="w-full h-10 " />
                        <h4 class="flex items-center justify-center w-full mt-0 text-lg font-bold text-center">
                             {{ formatter(value) }}
                        </h4>
                        <BudgetProgress
                            class="text-center rounded-lg "
                            :goal="toAssign.monthlyGoals.target"
                            :current="toAssign.monthlyGoals.balance"
                            :progress-class="['bg-secondary/10', 'bg-secondary/5']"
                        >
                            <section class="font-bold">
                                <h4 class="text-secondary">
                                    <MoneyPresenter :value="toAssign.monthlyGoals.balance" />
                                </h4>
                            </section>
                        </BudgetProgress>
                    </header>
                    <small class="mb-4">
                        {{ description }}
                    </small>

                    <NPopover v-if="isOverspent"  placement="bottom" >
                        <template #trigger>
                            <AtButton class="text-white rounded-md bg-black/30">
                                Fix this
                            </AtButton>
                        </template>
                        <div class="w-72 md:w-96">
                            <AtField label="From">
                                <Multiselect
                                    v-model="form.source_category_id"
                                    :options="categoryOptions"
                                    group-values="children"
                                    group-label="label"
                                    placeholder="Type to search"
                                    track-by="value"
                                    label="label"
                                    select-label=""
                                >
                                    <template v-slot:option="{ option }">
                                        <div class="flex justify-between text-sm group md:text-base">
                                            <span class="">{{ option.label || option.$groupLabel }}</span>
                                            <span class="text-success group-hover:text-white" v-if="option.available">{{ formatMoney(option.available) }}</span>
                                        </div>
                                </template>
                                </Multiselect>
                            </AtField>
                            <div class="flex items-center justify-end space-x-2">
                                <AtButton class="text-body-1" @click="clear">Cancel</AtButton>
                                <AtButton class="text-white rounded-md bg-success" @click="onAssignBudget()"> Save</AtButton>
                            </div>
                        </div>
                    </NPopover>
                </article>
            </template>
            <section class="text-center">
                <p>From last month: <MoneyPresenter :value="toAssign.movedFromLastMonth"/> </p>
                <p>Inflow: <MoneyPresenter :value="toAssign.availableForFunding"/> </p>
                <p>left over: <MoneyPresenter :value="toAssign.leftOver"/> </p>
                <p class="text-green-300">Total Available: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver + toAssign.movedFromLastMonth"/> </p>
                <p class="text-green-500">Total to budget: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver"/> </p>
                <p>Budgeted: <MoneyPresenter :value="toAssign.budgeted"/> </p>
                <p>Last month overspending: <MoneyPresenter :value="toAssign.overspending_from_previous_month"/> </p>
                <p class="text-red-500">Funded: <MoneyPresenter :value="toAssign.budgeted + toAssign.overspending_from_previous_month"/></p>
                <p>Balance: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver - toAssign.budgeted" /> </p>
            </section>
        </NPopover>

        <article class="grid grid-cols-2 overflow-hidden divide-x-2">
            <section>
                <slot name="activity" />
            </section>
            <section>
                <slot name="target" />
            </section>
        </article>
    </section>
</template>
