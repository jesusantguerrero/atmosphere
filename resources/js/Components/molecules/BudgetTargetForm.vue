<template>
    <section class="text-left border-b rounded-md bg-base-lvl-3 pb-4" v-auto-animate>
        <LogerTabButton @click="state.isActive=true" v-if="!state.isActive" class="bg-body-1/5 py-2 px-2 rounded-md text-body-1  w-full flex items-center">
            <span class="mr-2">
                <IconTarget />
            </span>
            Set target
        </LogerTabButton>

        <div v-else>
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
                <AtInput :number-format="true" v-model="form.amount">
                    <template #prefix>
                        <span class="flex items-center pl-2">
                            RD$
                        </span>
                    </template>
                    <template #suffix>
                        <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
                            <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
                        </NDropdown>
                    </template>
                </AtInput>
            </AtField>
    
            <section v-if="form.target_type == 'spending'">
                <AtButtonGroup
                    v-model="form.frequency"
                    :options="state.frequencies"
                    class="text-sm"
                    selected-class="text-white bg-primary"
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
    
            <div class="flex justify-between mt-4">
                <div class="flex font-bold">
                    <at-button class="block h-full text-gray-500 " @click="onCancel"> Cancel </at-button>
                </div>
                <at-button class="block h-full text-white rounded-md bg-primary" @click="onSubmit()"> Save </at-button>
            </div>
        </div>
    </section>
</template>

<script setup>
    import {  reactive, toRefs, watch, computed } from 'vue';
    import { AtButton, AtField, AtInput, AtErrorBag, AtButtonGroup } from "atmosphere-ui";
    import { useDatePager } from "vueuse-temporals"
    import { NSelect , NDropdown} from "naive-ui"
    import { useForm } from '@inertiajs/inertia-vue3';
    import { monthDays, WEEK_DAYS, FREQUENCY_TYPE, generateRandomColor } from "@/utils"
    import { makeOptions } from "@/utils/naiveui";
    import { format } from 'date-fns';
    import ColorSelector from './ColorSelector.vue';
    import LogerTabButton from '../atoms/LogerTabButton.vue';
    import IconTarget from '../icons/IconTarget.vue';
    // import IconPicker from '../IconPicker.vue';

    const props = defineProps({
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

    const emit = defineEmits(['cancel', 'deleted']);

    const state = reactive({
        form: useForm({
            category_id: null,
            parent_id: null,
            color: generateRandomColor(),
            name: props.category.name,
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
        ],
        isActive: Boolean(props.item)
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

    watch(
        () => props.category,
        () => {
            if (props.item) {
                state.form.reset()
                Object.keys(state.form.data()).forEach(key => {
                    state.form[key] = props.item[key] || ''
                })
                state.isActive = true
            } else {
                state.isActive = false
                state.form.reset()
            }
        },
        { deep: true, immediate: true });

    const onSubmit = () => {
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
            preserveScroll: true
        })
    }

    const onCancel = () => {
        if (props.item) {
            emit('cancel')
        } else {
            state.isActive = false;
        }
    }

    const { selectedSpan } =  useDatePager({nextMode: 'month'})

    const monthInstanceCount = computed(() => {
        return state.form.frequency == FREQUENCY_TYPE.WEEKLY &&
        selectedSpan.value.filter(
            day => {
                return format(day, 'iiiiii').toLowerCase() == state.form?.frequency_week_day?.toLowerCase()
            })
    })

    const { form, targetTypes, frequencies } = toRefs(state);


const options = [{
    name: 'setAssigned',
    label: 'Set Assigned'
},
{
    name: 'setAvailable',
    label: 'Set Available'
},
{
    name: 'clear',
    label: 'Clear'
}]

const setAmount = (amount) => {
    state.form.amount = amount;
}

const clear = () => {
    form.value.amount = 0
}

const handleOptions = (option) => {
    switch(option) {
        case 'setAssigned':
            setAmount(props.category.budgeted)
            break;
        case 'setAvailable':
            console.log(props.category)
            setAmount(props.category.available)
            break;
        default:
            clear()
            break;
    }
}
</script>
