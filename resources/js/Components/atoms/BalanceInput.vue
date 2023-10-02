<script lang="ts" setup>
    import { useForm } from "@inertiajs/vue3";
    import { computed, inject, ref, watch, nextTick } from "vue"
    import { NPopover } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";
    import Multiselect from "vue-multiselect";
    import { ICategory } from "@/domains/transactions/models";
    import LogerButton from "./LogerButton.vue";
    import InputMoney from "./InputMoney.vue";
    import LogerInput from "./LogerInput.vue";

    import { format, startOfMonth } from "date-fns";
    import { formatMoney } from "@/utils";

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

    const pageState = inject('pageState', {});

    const onAssignBudget = () => {
        if (BALANCE_STATUS.available || Number(props.category.budgeted) !== Number(form.amount)) {
            const month = format(startOfMonth(pageState.dates.endDate), 'yyyy-MM-dd');
            const field = status.value == BALANCE_STATUS.available ? 'source_category_id' : 'destination_category_id'

            form.transform(data => ({
                ...data,
                budgeted: BALANCE_STATUS.available ? data.amount : props.category.budgeted + data.amount,
                source_category_id: data.source_category_id?.value,
                destination_category_id: data.destination_category_id?.value,
                [field]: props.category.id,
                type: 'movement',
                date: month
            })).post(`/budgets/${props.category.id}/months/${month}`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess() {
                    showPopover.value = false;
                }
            });
        }
    }

    const hasAvailable = computed(() => {
        return status.value == BALANCE_STATUS.available
    })

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
            })).filter((category: ICategory) => !hasAvailable.value ? category.available > 0 : true)
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
<div class="flex justify-end text-right select-none" title="Money Available">
    <NPopover trigger="manual" placement="bottom"  @update:show="handleUpdateShow" :show="showPopover">
        <template #trigger>
            <div
                class="inline-flex items-center px-4 py-1 font-bold cursor-pointer flex-nowrap rounded-3xl min-w-max"
                :class="badgeClass"
                @click="toggle"
            >
                <span>
                    {{ formatter(value) }}
                </span>
                <slot name="suffix" />
            </div>
        </template>
        <div class="w-72 md:w-96">
            <AtField label="Move">
                <InputMoney :number-format="true" v-model="form.amount" >
                    <template #prefix>
                        <span class="flex items-center pl-2"> RD$ </span>
                      </template>
                </InputMoney>
            </AtField>
            <AtField label="To" v-if="status == BALANCE_STATUS.available">
                <Multiselect
                    v-model="form.destination_category_id"
                    :options="categoryOptions"
                    group-values="children"
                    group-label="label"
                    placeholder="Search category"
                    track-by="value"
                    label="label"
                    select-label=""
                >
                    <template v-slot:option="{ option }">
                        <div class="flex justify-between text-sm group md:text-base">
                            <span class="text-body-1/80" :class="{'font-bold': option.$groupLabel }">{{ option.label || option.$groupLabel }}</span>
                            <span class="font-bold text-secondary" v-if="option.available">
                                {{ formatMoney(option.available)
                            }}</span>
                        </div>
                    </template>
                </Multiselect>
            </AtField>
             <AtField label="From" v-else>
                <Multiselect
                    v-model="form.source_category_id"
                    :options="categoryOptions"
                    group-values="children"
                    group-label="label"
                    placeholder="Search category"
                    track-by="value"
                    label="label"
                    select-label=""

                >
                    <template v-slot:option="{ option }">
                        <div class="flex justify-between text-sm group md:text-base">
                            <span class="text-body-1/80" :class="{'font-bold': option.$groupLabel }">{{ option.label || option.$groupLabel }}</span>
                            <span class="font-bold text-secondary" v-if="option.available">{{ formatMoney(option.available) }}</span>
                        </div>
                    </template>
                </Multiselect>
            </AtField>
            <div class="flex items-center justify-end space-x-2">
                <AtButton class="text-body-1" @click="clear" :disabled="form.processing">
                    Cancel
                </AtButton>
                <LogerButton
                    class="text-white rounded-md bg-success"
                    @click="onAssignBudget()"
                    :processing="form.processing"
                >
                    Save
                </LogerButton>
            </div>
        </div>
    </NPopover>
</div>
</template>

