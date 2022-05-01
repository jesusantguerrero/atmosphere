<template>
    <div class="px-5 text-left border-b" :class="{'flex': !full }">
        <AtField label="Name">
            <AtInput v-model="form.name" />
            <AtErrorBag v-if="errors" :errors="errors" field="name" />
        </AtField>
        <AtField label="Category">
            <NSelect
                v-if="!isAddingGroup"
                filterable
                clearable
                size="large"
                v-model:value="form.account_id"
                :default-expand-all="true"
                :options="categoryOptions"
            >
                <template #action>
                <AtButton @click="isModalOpen = true" class="text-white bg-pink-500"> Add category </AtButton>
                </template>
            </NSelect>
            <AtInput v-model="form.name" v-else />
            <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
        </AtField>

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

        <AtField label="Amount">
            <AtInput type="number" v-model="form.amount" />
            <AtErrorBag v-if="errors" errors="errors" field="amount" />
        </AtField>

        <AtButtonGroup
            v-model="form.frequency"
            :options="state.frequencies"
            theme="primary"
            class="text-md"
        />

        <AtField label="Every" v-if="['MONTHLY', 'WEEKLY'].includes(form.frequency)">
            <NSelect
                filterable
                clearable
                size="large"
                v-model:value="form[frequencyUnit.field]"
                :default-expand-all="true"
                :options="frequencyUnit.options"
            />

            <div class="flex space-x-2 mt-2">
                <div v-for="instance in monthInstanceCount" class="h-2 w-full bg-primary-500" />
            </div>

            <AtErrorBag v-if="errors" :errors="errors" :field="frequencyUnit.field" />
        </AtField>

        <div class="flex justify-between mb-5">
            <div class="flex font-bold">
                <at-button class="block h-full text-red-500" @click="$emit('delete')"> Delete </at-button>
                <at-button class="block h-full text-gray-500 " @click="$emit('cancel')"> Cancel </at-button>
            </div>
            <at-button class="block h-full text-white bg-pink-500" @click="submit()"> Save </at-button>
        </div>
    </div>
</template>

<script setup>
    import { inject, reactive, toRefs, watch, computed, onMounted} from 'vue';
    import { AtButton, AtField, AtInput, AtErrorBag, AtButtonGroup } from "atmosphere-ui";
    import { useDatePager } from "vueuse-temporals"
    import { NSelect } from "naive-ui"
    import { useForm } from '@inertiajs/inertia-vue3';
    import { monthDays, WEEK_DAYS, FREQUENCY_TYPE } from "@/utils"
    import { makeOptions } from "@/utils/naiveui";
    import { format } from 'date-fns';

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
        item: {
            type: Object,
            default: () => {}
        }
    });

    const categoryOptions = inject('categoryOptions', []);

    const state = reactive({
        isAddingGroup: false,
        form: useForm({
            account_id: null,
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
        Object.keys(state.form.data()).forEach(key => {
            state.form[key] = props.item[key]
        })
        console.log(props.item)
    }), { deep: true, immediate: true }

    const submit = () => {
        const methods = {
            update: {
                method: 'put',
                url: props.item.id && `/budgets/${props.item.id}`
            },
            save: {
                method: 'post',
                url: '/budgets'
            }
        }
        const endpoint = methods[props.item.id ? 'update' : 'save']
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
                return format(day, 'iiiiii').toLowerCase() == state.form.frequency_week_day.toLowerCase()
            })
    })

    const { form } = toRefs(state);
</script>
