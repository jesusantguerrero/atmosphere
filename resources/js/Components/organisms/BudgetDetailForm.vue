<template>
    <section class="px-5 pt-2 text-left border-b rounded-md space-y-4 shadow-xl bg-base-lvl-3 pb-4" :class="{'flex': !full }">
        <header class="flex items-center justify-between mt-2 mb-2">
            <AtInput 
                v-model="category.name" 
                class="border-transparent cursor-pointer hover:text-primary hover:border-primary" 
                rounded
            >
                <template #prefix>
                    <div class=" px-1 flex items-center justify-center">
                        <ColorSelector v-model="form.color" />
                    </div>
                </template>
            </AtInput>
        </header>

        <BudgetTargetForm 
            :parent-id="parentId"
            :category="category"
            :item="item"
            @delete="$emit('delete')"
        />

        <div>
            Auto-Assign
        </div>

        <div>
            Available Balance
        </div>

        <div>
            Notes
        </div>
    </section>
</template>

<script setup>
    import {  reactive, toRefs, watch, computed } from 'vue';
    import { AtButton, AtField, AtInput, AtErrorBag, AtButtonGroup } from "atmosphere-ui";
    import { useDatePager } from "vueuse-temporals"
    import { NSelect , NDropdown} from "naive-ui"
    import { useForm } from '@inertiajs/inertia-vue3';
    import { format } from 'date-fns';
    
    import ColorSelector from '../molecules/ColorSelector.vue';
    import LogerTabButton from '../atoms/LogerTabButton.vue';
    import BudgetTargetForm from '../molecules/BudgetTargetForm.vue';
    
    import { monthDays, WEEK_DAYS, FREQUENCY_TYPE, generateRandomColor } from "@/utils"
    import { makeOptions } from "@/utils/naiveui";
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

    watch(
        () => props.item,
        (item) => {
            if (item) {
                state.form.reset()
                Object.keys(state.form.data()).forEach(key => {
                    state.form[key] = item[key] || state.form[key]
                })
            } else {
                state.form.reset()
            }
        },
        { deep: true, immediate: true });

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
        }))[endpoint.method](endpoint.url)
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
