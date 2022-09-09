<template>
    <section class="px-5 text-left border-b bg-base-lvl-3 rounded-md shadow-xl pt-2" :class="{'flex': !full }">
        <header class="mb-8 flex justify-between items-center mt-2">
            <AtInput v-model="category.name" class="border-transparent hover:text-primary hover:border-primary cursor-pointer" rounded />
            <AtButton class="block h-full text-red-500" @click="$emit('delete')">
                <i class="fa fa-trash"></i>
            </AtButton>
        </header>

        <AtField label="Target Type">
            <NSelect
                filterable
                clearable
                size="large"
                v-model:value="form.target_type"
                :default-expand-all="true"
                :options="state.targetTypes"
            />
            <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
        </AtField>

        <AtField label="Amount" errors="errors" field="amount">
            <AtInput type="number" v-model="form.amount" />
        </AtField>

        <section v-if="form.target_type == 'spending'">
            <AtButtonGroup
                v-model="form.frequency"
                :options="state.frequencies"
                class="text-lg"
                selected-class="bg-primary text-white"
            />

            <AtField
                label="Every"
                v-if="['MONTHLY', 'WEEKLY'].includes(form.frequency)"
                :errors="errors" :field="frequencyUnit.field">
                <NSelect
                    filterable
                    clearable
                    size="large"
                    v-model:value="form[frequencyUnit.field]"
                    :default-expand-all="true"
                    :options="frequencyUnit.options"
                />

                <div class="flex mt-2 space-x-2">
                    <div v-for="instance in monthInstanceCount" class="w-full h-2 bg-primary" :key="instance" />
                </div>
            </AtField>
        </section>

        <div class="flex justify-between mb-4 mt-4">
            <div class="flex font-bold">
                <at-button class="block h-full text-gray-500 " @click="$emit('cancel')"> Cancel </at-button>
            </div>
            <at-button class="block h-full text-white bg-primary rounded-md" @click="submit()"> Save </at-button>
        </div>
    </section>
</template>

<script setup>
    import {  reactive, toRefs, watch, computed } from 'vue';
    import { AtButton, AtField, AtInput, AtErrorBag, AtButtonGroup } from "atmosphere-ui";
    import { useDatePager } from "vueuse-temporals"
    import { NSelect } from "naive-ui"
    import { useForm } from '@inertiajs/inertia-vue3';
    import { monthDays, WEEK_DAYS, FREQUENCY_TYPE } from "@/utils"
    import { makeOptions } from "@/utils/naiveui";
    import { format } from 'date-fns';
    // import IconPicker from '../IconPicker.vue';

    const props = defineProps({
        isAddingGroup: {
            type: Boolean,
            default: false
        },
        parentId: {
            type: Number,
            default: null
        },
        full: {
            type: Boolean,
            default: false
        },
        category: {
            type: Object,
            required: true
        },
        item: {
            type: Object,
            default: () => {}
        }
    });

    const state = reactive({
        isAddingGroup: false,
        form: useForm({
            category_id: null,
            parent_id: null,
            name: '',
            amount: 0,
            assigned: 0,
            target_type: '',
            frequency: 'MONTHLY',
            frequency_month_date: null,
            frequency_week_day: null,
            frequency_date: null,
            frequency_interval: 0,
            frequency_interval_unit: 0,
        }),
        targetTypes: [
            {
                value: 'spending',
                label: 'Spending',
                description: `For groceries, holidays, vacations
                Fund up to an amount with the ability to spend from it along the way
                `,

            }, {
                value: 'saving_balance',
                label: 'Saving Balance',
                description: `down payments, emergency fund
                Save this amount over time and maintain the balance by replenishing any money spent
                `
            }, {
                value: 'savings_monthly',
                label: 'Savings Monthly',
                description: `For saving goals with unknown targets
                Contribute this amount every month until you disable this target
                `
            }, {
                value: 'debt_monthly_payment',
                label: 'Debt Monthly Payment',
                description: `Use for: Mortgage, student loans, auto loans, etc
                Budget for payments until you are debt free
                `
            }
        ],
        frequencies: [
            {
                value: 'MONTHLY',
                label: 'Monthly'
            },
            {
                value: 'WEEKLY',
                label: 'Weekly'
            },
            {
                value: 'DATE',
                label: 'By Date'
            }
        ]
    })

    const frequencyUnit = computed(() => {
        const names = {
            MONTHLY: {
                field: 'frequency_month_date',
                options: monthDays()
            },
            WEEKLY: {
                field: 'frequency_week_day',
                options: makeOptions(WEEK_DAYS)
            }
        }
        return {
           ...names[state.form.frequency],
        }
    })

    watch(() =>
        props.item,
        () => {
            if (props.item) {
                Object.keys(state.form.data()).forEach(key => {
                    state.form[key] = props.item[key] || state.form[key]
                })
            } else {
                state.form.reset()
            }
    }), { deep: true, immediate: true }

    const submit = () => {
        const methods = {
            update: {
                method: 'put',
                url: props.item?.id && `/budgets/${props.category.id}/targets/${props.item.id}`
            },
            save: {
                method: 'post',
                url: `/budgets/${props.category.id}/targets`
            }
        }
        const endpoint = methods[props.item?.id ? 'update' : 'save']
        state.form.transform((data) => ({
            ...data,
            parent_id: data.parent_id || state.parentId,
        }))[endpoint.method](endpoint.url, {
            onSuccess: () => {
                state.form.reset();
            }
        })
    }

    const { selectedSpan } =  useDatePager({
        nextMode: 'month'
    })

    const monthInstanceCount = computed(() => {
        return state.form.frequency == FREQUENCY_TYPE.WEEKLY &&
        selectedSpan.value.filter(
            day => {
                return format(day, 'iiiiii').toLowerCase() == state.form?.frequency_week_day?.toLowerCase()
            })
    })

    const { form, targetTypes, frequencies } = toRefs(state);
</script>
