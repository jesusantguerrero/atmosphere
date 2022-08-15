<template>
    <section
        class="px-5 py-1 cursor-pointer rounded-3xl text-body-1 flex justify-between"
        :class="badgeClass"
        @click="toggle"
    >
        <article class="flex flex-col h-10 justify-center">
            <p> {{ formatter(value) }} </p>
            <small v-if="isOverspent">
                You assigned more than you have
            </small>
        </article>

        <NPopover v-if="isOverspent" trigger="manual" placement="bottom"  @update:show="handleUpdateShow" :show="showPopover">
        <template #trigger>
            <AtButton class="rounded-md bg-white/80">
                Fix this
            </AtButton>
        </template>
        <div>
             <AtField label="From">
                <NSelect
                    filterable
                    clearable
                    size="large"
                    v-model:value="form.source_category_id"
                    :default-expand-all="true"
                    :options="categoryOptions"
                />
            </AtField>
            <div class="flex space-x-2 justify-end items-center">
                <AtButton class="text-body-1" @click="clear">Cancel</AtButton>
                <AtButton class="bg-success text-white rounded-md" @click="onAssignBudget()"> Save</AtButton>
            </div>
        </div>
    </NPopover>
    </section>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3";
    import { Inertia } from "@inertiajs/inertia";
    import { computed, inject, ref } from "vue"
    import { NPopover, NSelect } from "naive-ui";
    import { AtField, AtButton } from "atmosphere-ui";
    import { format, startOfMonth } from "date-fns";
    import ExactMath from "exact-math";

    const props = defineProps({
        value: {
            type: Number
        },
        formatter: {
            type: Function,
            default() {
                return (value) => {
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
        good: 'bg-success',
        danger: 'bg-error/50',
        needs: 'bg-warning',
        overspend: 'bg-warning',
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

    const isOverspent = computed(() => {
        return status.value == BALANCE_STATUS.overspent
    })

    const badgeClass = computed(() => {
        let themeColor = theme.default
        if (status.value === BALANCE_STATUS.available) {
            themeColor = theme.good
        } else if (status.value === BALANCE_STATUS.overspent) {
            themeColor = theme.danger
        }
        return [themeColor]
    })

    const form = useForm({
        amount: Math.abs(props.value),
        source_category_id: null,
        destination_category_id: null,
    });

    const onAssignBudget = () => {
        if (Number(form.amount) > 0) {
            const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
            const field = status.value == BALANCE_STATUS.available ? 'source_category_id' : 'destination_category_id'
            form.transform(data => ({
                ...data,
                budgeted: Math.abs(props.value),
                [field]: props.category.id,
                'type': 'movement'
            })).post(`/budgets/${props.category.id}/months/${month}`, {
                onSuccess() {
                    Inertia.reload({
                        preserveScroll: true,
                    })
                }
            });
        }
    }

    const categoryOptions = inject('categoryOptions', [])


    const showPopover = ref(false)
    const clear = () => {
        form.reset();
        showPopover.value = false;
    }
    const toggle = () => {
        showPopover.value = !showPopover.value
    }
</script>
