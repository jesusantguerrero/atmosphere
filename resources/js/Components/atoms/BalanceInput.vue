<script lang="ts" setup>
    import { useForm } from "@inertiajs/vue3";
    import { computed, inject, ref } from "vue"
    import { NPopover } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";
    import Multiselect from "vue-multiselect";
    import { ICategory } from "@/domains/transactions/models";
    import LogerButton from "./LogerButton.vue";
    import InputMoney from "./InputMoney.vue";

    import { formatMoney } from "@/utils";

    const emit = defineEmits(['move']);

    const props = withDefaults(defineProps<{
        value: number | string;
        formatter: Function;
        category: ICategory;
    }>(), {
        formatter: () => {
                return (value: string) => {
                    return value
                }
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

    const onMoveFromBudget = () => {
        if (BALANCE_STATUS.available || Number(props.category.budgeted) !== Number(form.amount)) {
            const field = status.value == BALANCE_STATUS.available ? 'sourceCategoryId' : 'destinationCategoryId'

            const data = form.data();

            emit('move', {
                ...form.data(),
                budgeted: BALANCE_STATUS.available ? data.amount : props.category.budgeted + data.amount,
                sourceCategoryId: data.source_category_id?.value,
                destinationCategoryId: data.destination_category_id?.value,
                [field]: props.category.id,
                category: props.category
            })

            showPopover.value = false;
        }
    }

    const hasAvailable = computed(() => {
        return status.value == BALANCE_STATUS.available
    })

    const categories = inject('categories', ref([]))
    const categoryOptions = computed(() => {
        return categories.value?.map?.(item => ({
            value: item.id,
            key: item.id,
            label: item.name,
            type: 'group',
            children: item.subCategories?.map?.(category => ({
                value: category.id,
                label: category.name,
                available: category.available || 0,
            })).filter((category: ICategory) => !hasAvailable.value ? category.available > 0 : true) ?? []
        })) ?? []
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
    <NPopover trigger="manual" placement="bottom"  :show="showPopover">
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
                                {{ formatMoney(option.available) }}</span>
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
                    @click="onMoveFromBudget()"
                    :processing="form.processing"
                >
                    Save
                </LogerButton>
            </div>
        </div>
    </NPopover>
</div>
</template>

