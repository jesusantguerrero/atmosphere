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
            <LogerTabButton @click="$emit('close')">
                <IconClose />
            </LogerTabButton>
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
            <BudgetMoneyLine title="Left Over from last month" :value="category.prevMonthLeftOver" />
            <BudgetMoneyLine title="Assigned this month" :value="category.budgeted" />
            <BudgetMoneyLine title="Cash expending" :value="category.activity" />
            <BudgetMoneyLine title="Credit expending" :value="category.activity" />
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

    import { monthDays, WEEK_DAYS, FREQUENCY_TYPE, generateRandomColor, recurrenceTypes } from "@/utils"
    import { makeOptions } from "@/utils/naiveui";
    import { targetTypes } from '@/domains/budget';
    import IconClose from '../icons/IconClose.vue';
import BudgetMoneyLine from '../molecules/BudgetMoneyLine.vue';

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

    const form = useForm({
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
           ...names[recurrenceTypes],
        }
    })

    watch(
        () => props.item,
        (item) => {
            if (item) {
                form.reset()
                Object.keys(form.data()).forEach(key => {
                    form[key] = item[key] || form[key]
                })
            } else {
                form.reset()
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
        form.transform((data) => ({
            ...data,
            parent_id: data.parent_id || props.parentId,
        }))[endpoint.method](endpoint.url)
    }

    const { selectedSpan } =  useDatePager({
        nextMode: 'month'
    })

    const monthInstanceCount = computed(() => {
        return form.frequency == FREQUENCY_TYPE.WEEKLY &&
        selectedSpan.value.filter(
            day => {
                return format(day, 'iiiiii').toLowerCase() == state.form?.frequency_week_day?.toLowerCase()
            })
    })
</script>
