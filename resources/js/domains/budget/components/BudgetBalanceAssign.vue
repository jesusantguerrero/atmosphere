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
        },
        scheduledTotal: {
            type: Number,
            default: 0
        }
    })

    const afterScheduled = computed(() => Number(props.value ?? 0) - Number(props.scheduledTotal ?? 0))

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

    const categories = inject('categories', ref([]))
    const categoryOptions = computed(() => {
        return categories.value?.map(item => ({
            value: item.id,
            key: item.id,
            label: item.name,
            type: 'group',
            children: item.subCategories.map(category => ({
                value: category.id,
                label: category.name,
                available: category.display_id === 'ready_to_assign'
                    ? Number(props.toAssign?.balance ?? 0)
                    : (category.available || 0),
            }))
        }))
    })

    const showPopover = ref(false)
    const clear = () => {
        form.reset();
        showPopover.value = false;
    }

    // ----- One-shot split: Ahorro / Gasto Personal -----
    const findCategoryByName = (name: string) => {
        for (const group of categories.value || []) {
            const found = group.subCategories?.find((c: any) => c.name === name)
            if (found) {
                return found
            }
        }
        return null
    }

    const splitTargets = computed(() => ({
        savings: findCategoryByName('Ahorro'),
        personal: findCategoryByName('Gasto Personal'),
    }))

    const canSplit = computed(() => Number(props.value) > 0
        && splitTargets.value.savings
        && splitTargets.value.personal
    )

    const splitForm = useForm({
        savings_amount: 0,
        personal_amount: 0,
    })

    const splitTotal = computed(() => Number(splitForm.savings_amount || 0) + Number(splitForm.personal_amount || 0))

    const splitRemaining = computed(() => Number(props.value || 0) - splitTotal.value)

    const splitExceedsAvailable = computed(() => splitTotal.value > Number(props.value || 0))

    const presetSplit = (savingsRatio: number, personalRatio: number) => {
        const total = Number(props.value || 0)
        splitForm.savings_amount = Math.round(total * savingsRatio * 100) / 100
        splitForm.personal_amount = Math.round(total * personalRatio * 100) / 100
    }

    const showSplitPopover = ref(false)

    const openSplit = () => {
        const half = Math.round((Number(props.value || 0) / 2) * 100) / 100
        splitForm.savings_amount = half
        splitForm.personal_amount = Number(props.value || 0) - half
        showSplitPopover.value = true
    }

    const splitError = ref<string | null>(null)

    const submitSplit = () => {
        splitError.value = null
        if (!canSplit.value || splitTotal.value <= 0 || splitExceedsAvailable.value) {
            return
        }
        const month = format(startOfMonth(pageState?.dates?.endDate), 'yyyy-MM-dd')
        const sourceId = props.category.id

        splitForm.transform(() => ({
            date: month,
            splits: [
                { destination_category_id: splitTargets.value.savings.id, amount: Number(splitForm.savings_amount) },
                { destination_category_id: splitTargets.value.personal.id, amount: Number(splitForm.personal_amount) },
            ].filter(s => s.amount > 0),
        })).post(`/budgets/${sourceId}/months/${month}/split`, {
            preserveScroll: true,
            onSuccess() {
                showSplitPopover.value = false
                splitForm.reset()
                router.reload({ preserveScroll: true })
            },
            onError(errors) {
                const first = Object.values(errors)[0]
                splitError.value = (Array.isArray(first) ? first[0] : first) || 'Request failed'
            },
        })
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
                        <h4 class="flex flex-col items-center justify-center w-full mt-0 font-bold text-center">
                            <span class="text-lg">{{ formatter(value) }}</span>
                            <span
                                v-if="scheduledTotal > 0"
                                class="text-xs font-normal opacity-80"
                            >
                                {{ $t('After scheduled') }}: {{ formatter(afterScheduled) }}
                            </span>
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

                    <NPopover
                        v-if="canSplit"
                        v-model:show="showSplitPopover"
                        placement="bottom"
                        trigger="click"
                    >
                        <template #trigger>
                            <AtButton
                                class="mb-3 text-white rounded-md bg-black/30"
                                @click.stop="openSplit"
                            >
                                {{ $t('Allocate leftover') }}
                            </AtButton>
                        </template>
                        <div class="w-80 md:w-96 space-y-4 text-gray-800">
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <span class="text-sm font-medium text-gray-600">{{ $t('Available') }}</span>
                                <span class="text-base font-bold text-success">{{ formatter(value) }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-gray-200 hover:bg-gray-50 hover:border-primary"
                                    @click.stop="presetSplit(0.5, 0.5)"
                                >50 / 50</button>
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-gray-200 hover:bg-gray-50 hover:border-primary"
                                    @click.stop="presetSplit(1, 0)"
                                >{{ $t('All to Savings') }}</button>
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-gray-200 hover:bg-gray-50 hover:border-primary"
                                    @click.stop="presetSplit(0, 1)"
                                >{{ $t('All to Spending') }}</button>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">{{ $t('Ahorro') }}</label>
                                    <div class="relative">
                                        <span class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">$</span>
                                        <input
                                            v-model.number="splitForm.savings_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full py-2 pl-7 pr-3 text-gray-800 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">{{ $t('Gasto Personal') }}</label>
                                    <div class="relative">
                                        <span class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">$</span>
                                        <input
                                            v-model.number="splitForm.personal_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full py-2 pl-7 pr-3 text-gray-800 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2 text-sm border-t border-gray-200">
                                <span class="text-gray-600">{{ $t('Unassigned') }}</span>
                                <span
                                    class="font-bold"
                                    :class="Math.abs(splitRemaining) < 0.01 ? 'text-success' : splitRemaining < 0 ? 'text-error' : 'text-warning'"
                                >
                                    {{ formatter(splitRemaining) }}
                                </span>
                            </div>

                            <p v-if="splitError" class="text-xs text-error">{{ splitError }}</p>

                            <div class="flex items-center justify-end pt-2 space-x-2">
                                <button
                                    type="button"
                                    class="px-4 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-100"
                                    @click.stop="showSplitPopover = false"
                                >
                                    {{ $t('Cancel') }}
                                </button>
                                <button
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white rounded-md bg-success hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="splitExceedsAvailable || splitTotal <= 0 || splitForm.processing"
                                    @click.stop="submitSplit"
                                >
                                    {{ $t('Save') }}
                                </button>
                            </div>
                        </div>
                    </NPopover>

                    <NPopover v-if="isOverspent"  placement="bottom" trigger="click">
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
                                            <span :class="option.$groupLabel ? 'text-gray-500 font-bold' : 'text-gray-800'">
                                                {{ option.label || option.$groupLabel }}
                                            </span>
                                            <span
                                                v-if="option.available !== undefined && option.available !== null && option.available !== 0"
                                                :class="option.available > 0 ? 'text-success group-hover:text-white' : 'text-error group-hover:text-white'"
                                            >
                                                {{ formatMoney(option.available) }}
                                            </span>
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
                <p>{{ $t('Scheduled this period') }}: <MoneyPresenter :value="scheduledTotal"/> </p>
                <p class="text-blue-300">{{ $t('After scheduled') }}: <MoneyPresenter :value="afterScheduled"/> </p>
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
