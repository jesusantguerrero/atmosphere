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
                        <ColorSelector 
                            v-model="category.color"
                            @update:modelValue="onBlur(category)"
                        />
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

        <div v-if="false">
            Auto-Assign
        </div>

        <div>
            Available Balance
            <BudgetMoneyLine title="Left Over from last month" :value="category.prevMonthLeftOver" />
            <BudgetMoneyLine title="Assigned this month" :value="category.budgeted" />
            <BudgetMoneyLine title="Cash expending" :value="category.activity" />
            <BudgetMoneyLine title="Credit expending" :value="category.activity" />
        </div>

        <div v-if="false">
            Notes
        </div>
    </section>
</template>

<script setup>
    import { watch } from 'vue';
    import { AtInput } from "atmosphere-ui";
    import { useForm } from '@inertiajs/inertia-vue3';

    import ColorSelector from '../molecules/ColorSelector.vue';
    import LogerTabButton from '../atoms/LogerTabButton.vue';
    import BudgetTargetForm from '../molecules/BudgetTargetForm.vue';

    import { generateRandomColor } from "@/utils"
    import IconClose from '../icons/IconClose.vue';
    import BudgetMoneyLine from '../molecules/BudgetMoneyLine.vue';
    import { Inertia } from '@inertiajs/inertia';

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

    const emit = defineEmits(['update:category'])

    const form = useForm({
        category_id: null,
        parent_id: null,
        color: props.category.color || generateRandomColor(),
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

    
    const onBlur = (category) => {
        axios.put(`/categories/${props.category.id}?json=true`, category)
        .then(() => {
            emit('update:category', category)
        })
    }
</script>
