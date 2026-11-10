<script lang="ts" setup>
    import { useForm } from "@inertiajs/vue3";
    import { router, usePage } from "@inertiajs/vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover, } from "naive-ui";
    // @ts-ignore
    import { AtField, AtButton } from "atmosphere-ui";
    import { format, startOfMonth } from "date-fns";
    import Multiselect from "vue-multiselect";

    import InfoHint from "@/Components/atoms/InfoHint.vue";
    import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

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

    /** Zero-based budget: 0 is the goal, so each state says what to do next. */
    const BALANCE_STATUS = {
        overspent: {
            name: 'overspent',
            label: 'You assigned too much',
            description: 'You assigned more than you have. Take money from another category or add funds to balance the month.',
            heroClass: 'bg-error/10 border-error/30',
            labelClass: 'text-error',
            amountClass: 'text-error',
        },
        available: {
            name: 'available',
            label: 'Ready to assign',
            description: 'You have unassigned money remaining. Assign it to categories, savings, or credit card payments to balance the month.',
            heroClass: 'bg-primary/10 border-primary/30',
            labelClass: 'text-primary',
            amountClass: 'text-body',
        },
        empty: {
            name: 'empty',
            label: 'All balanced',
            description: 'Every peso is assigned. Nice work.',
            heroClass: 'bg-base-lvl-2 border-body-1/15',
            labelClass: 'text-success',
            amountClass: 'text-success',
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

    const current = computed(() => BALANCE_STATUS[status.value])

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

    // ----- One-shot split: configurable savings / spending defaults -----
    const findCategoryById = (id: number | undefined | null) => {
        if (!id) {
            return null
        }
        for (const group of categories.value || []) {
            const found = group.subCategories?.find((c: any) => c.id === id)
            if (found) {
                return found
            }
        }
        return null
    }

    const splitTargets = computed(() => {
        const defaults = (usePage().props.budgetDefaults || {}) as Record<string, number>
        return {
            savings: findCategoryById(defaults.savings),
            personal: findCategoryById(defaults.spending),
        }
    })

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
    <!-- Hero of the zero-based budget: the amount still to assign leads the
         page, with the action that brings it to 0 right beside it. The
         default slot holds the reconcile line as a footer. -->
    <section
        class="rounded-xl border px-4 py-4 md:px-6 md:py-5"
        :class="current.heroClass"
        aria-live="polite"
    >
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wide" :class="current.labelClass">
                    <IMdiCheckCircle v-if="status === BALANCE_STATUS.empty.name" aria-hidden="true" />
                    {{ $t(current.label) }}
                    <InfoHint :title="$t('Ready to assign')" :body="$t('ready_to_assign_hint')" />
                </p>
                <p class="text-3xl md:text-4xl font-extrabold leading-tight tabular-nums" :class="current.amountClass">
                    {{ formatter(value) }}
                </p>
                <p class="mt-1 text-sm text-body-1 max-w-prose">{{ $t(current.description) }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                    <NPopover
                        v-if="canSplit"
                        v-model:show="showSplitPopover"
                        placement="bottom"
                        trigger="click"
                    >
                        <template #trigger>
                            <AtButton
                                class="text-white rounded-md bg-primary hover:opacity-90 font-semibold shrink-0"
                                @click.stop="openSplit"
                            >
                                {{ $t('Allocate leftover') }}
                            </AtButton>
                        </template>
                        <div class="w-80 md:w-96 space-y-4 text-body">
                            <div class="flex items-center justify-between pb-2 border-b border-base-lvl-2">
                                <span class="text-sm font-medium text-body-1">{{ $t('Available') }}</span>
                                <span class="text-base font-bold text-success">{{ formatter(value) }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-base-lvl-2 hover:bg-base-lvl-2 hover:border-primary"
                                    @click.stop="presetSplit(0.5, 0.5)"
                                >50 / 50</button>
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-base-lvl-2 hover:bg-base-lvl-2 hover:border-primary"
                                    @click.stop="presetSplit(1, 0)"
                                >{{ $t('All to Savings') }}</button>
                                <button
                                    type="button"
                                    class="px-2 py-1.5 text-xs font-medium rounded-md border border-base-lvl-2 hover:bg-base-lvl-2 hover:border-primary"
                                    @click.stop="presetSplit(0, 1)"
                                >{{ $t('All to Spending') }}</button>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-body-1">
                                        {{ splitTargets.savings?.name ?? $t('Savings') }}
                                    </label>
                                    <div class="relative">
                                        <span class="absolute text-body-1/70 -translate-y-1/2 left-3 top-1/2">$</span>
                                        <input
                                            v-model.number="splitForm.savings_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full py-2 pl-7 pr-3 text-body border border-base-lvl-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-body-1">
                                        {{ splitTargets.personal?.name ?? $t('Personal Spending') }}
                                    </label>
                                    <div class="relative">
                                        <span class="absolute text-body-1/70 -translate-y-1/2 left-3 top-1/2">$</span>
                                        <input
                                            v-model.number="splitForm.personal_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full py-2 pl-7 pr-3 text-body border border-base-lvl-2 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2 text-sm border-t border-base-lvl-2">
                                <span class="text-body-1">{{ $t('Unassigned') }}</span>
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
                                    class="px-4 py-2 text-sm text-body-1 rounded-md hover:text-body hover:bg-base-lvl-1"
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
                            <AtButton class="text-white rounded-md bg-error/80 hover:bg-error font-semibold shrink-0" @click.stop>
                                {{ $t('Fix this') }}
                            </AtButton>
                        </template>
                        <div class="w-72 md:w-96">
                            <AtField :label="$t('From')">
                                <Multiselect
                                    v-model="form.source_category_id"
                                    :options="categoryOptions"
                                    group-values="children"
                                    group-label="label"
                                    :placeholder="$t('Type to search')"
                                    track-by="value"
                                    label="label"
                                    select-label=""
                                >
                                    <template v-slot:option="{ option }">
                                        <div class="flex justify-between text-sm group md:text-base">
                                            <span :class="option.$groupLabel ? 'text-body-1 font-bold' : 'text-body'">
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
                                <AtButton class="text-body-1" @click="clear">{{ $t('Cancel') }}</AtButton>
                                <AtButton
                                    class="text-white rounded-md bg-success"
                                    :class="form.processing ? 'pointer-events-none opacity-50' : ''"
                                    :disabled="form.processing"
                                    @click="onAssignBudget()">{{ $t('Save') }}</AtButton>
                            </div>
                        </div>
                    </NPopover>

                <NPopover trigger="click" placement="bottom-end">
                    <template #trigger>
                        <AtButton class="text-body-1 hover:text-body rounded-md text-sm shrink-0">
                            {{ $t('Breakdown') }}
                        </AtButton>
                    </template>
                    <section class="space-y-0.5 text-sm">
                <p>{{ $t('From last month') }}: <MoneyPresenter :value="toAssign.movedFromLastMonth"/> </p>
                <p>{{ $t('Inflow') }}: <MoneyPresenter :value="toAssign.availableForFunding"/> </p>
                <p>{{ $t('Left over') }}: <MoneyPresenter :value="toAssign.leftOver"/> </p>
                <p>{{ $t('Scheduled this period') }}: <MoneyPresenter :value="scheduledTotal"/> </p>
                <p class="text-blue-300">{{ $t('After scheduled') }}: <MoneyPresenter :value="afterScheduled"/> </p>
                <p class="text-green-300">{{ $t('Total available') }}: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver + toAssign.movedFromLastMonth"/> </p>
                <p class="text-green-500">{{ $t('Total to budget') }}: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver"/> </p>
                <p>{{ $t('Budgeted') }}: <MoneyPresenter :value="toAssign.budgeted"/> </p>
                <p>{{ $t('Last month overspending') }}: <MoneyPresenter :value="toAssign.overspending_from_previous_month"/> </p>
                <p class="text-red-500">{{ $t('Funded') }}: <MoneyPresenter :value="toAssign.budgeted + toAssign.overspending_from_previous_month"/></p>
                <p>{{ $t('Balance') }}: <MoneyPresenter :value="toAssign.availableForFunding + toAssign.leftOver - toAssign.budgeted" /> </p>
                    </section>
                </NPopover>
            </div>
        </div>

        <div v-if="$slots.default" class="mt-4 border-t border-body-1/10 pt-3">
            <slot />
        </div>
    </section>
</template>
