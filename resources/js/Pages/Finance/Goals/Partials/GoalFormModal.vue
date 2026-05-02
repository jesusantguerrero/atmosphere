<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { AtButton, AtField } from 'atmosphere-ui';
import { NSelect, NDatePicker } from 'naive-ui';
import { differenceInCalendarMonths, format } from 'date-fns';

import Modal from '@/Components/atoms/Modal.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { formatMoney } from '@/utils';
import { targetTypes } from '@/domains/budget';

interface CategoryOption {
    id: number;
    name: string;
    group_name: string | null;
}

const props = defineProps<{
    show: boolean;
    categories: CategoryOption[];
}>();

const emit = defineEmits<{
    'update:show': [value: boolean];
    created: [];
}>();

const { t } = useI18n();

const goalTargetTypes = computed(() =>
    targetTypes.filter((t) => t.value === 'saving_balance' || t.value === 'savings_monthly')
);

const form = useForm({
    category_id: null as number | null,
    target_type: 'saving_balance' as 'saving_balance' | 'savings_monthly',
    amount: 0,
    frequency: 'DATE' as 'MONTHLY' | 'DATE',
    frequency_date: null as Date | null,
    notify: false,
});

const showDatePicker = computed(() => form.target_type === 'saving_balance');

const categoryOptions = computed(() => {
    const groups = new Map<string, CategoryOption[]>();
    for (const cat of props.categories) {
        const key = cat.group_name || t('Other');
        if (!groups.has(key)) {
            groups.set(key, []);
        }
        groups.get(key)!.push(cat);
    }
    return Array.from(groups.entries()).map(([groupName, items]) => ({
        type: 'group' as const,
        label: groupName,
        key: groupName,
        children: items.map((c) => ({ value: c.id, label: c.name })),
    }));
});

const monthlyHint = computed(() => {
    if (form.target_type !== 'saving_balance' || !form.frequency_date || !form.amount) {
        return null;
    }
    const target = form.frequency_date instanceof Date ? form.frequency_date : new Date(form.frequency_date);
    if (isNaN(target.getTime())) {
        return null;
    }
    const months = differenceInCalendarMonths(target, new Date());
    if (months <= 0) {
        return t('Need {amount} / month to reach this goal', { amount: formatMoney(form.amount) });
    }
    const perMonth = form.amount / months;
    return t('Need {amount} / month to reach this goal', { amount: formatMoney(perMonth) });
});

watch(
    () => form.target_type,
    (val) => {
        if (val === 'savings_monthly') {
            form.frequency = 'MONTHLY';
            form.frequency_date = null;
        } else {
            form.frequency = 'DATE';
        }
    }
);

const reset = (): void => {
    form.reset();
    form.clearErrors();
};

const close = (): void => {
    emit('update:show', false);
    reset();
};

const submit = (): void => {
    if (!form.category_id) {
        form.setError('category_id', t('Please choose a category'));
        return;
    }

    form.transform((data) => ({
        ...data,
        frequency_date: data.frequency_date ? format(new Date(data.frequency_date), 'yyyy-MM-dd') : null,
    })).post(route('budget.target.store', { category: form.category_id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('created');
            close();
        },
    });
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="close">
        <header class="flex items-center px-6 py-4 font-bold bg-base-lvl-3 text-body">
            <span class="text-lg">{{ $t('New goal') }}</span>
        </header>

        <section class="px-6 py-5 space-y-4 bg-base-lvl-3 text-body">
            <AtField :label="$t('Category')" :errors="form.errors" field="category_id">
                <NSelect
                    filterable
                    clearable
                    size="large"
                    v-model:value="form.category_id"
                    :options="categoryOptions"
                    :placeholder="$t('Choose a category')"
                />
                <p v-if="form.errors.category_id" class="mt-1 text-xs text-error">{{ form.errors.category_id }}</p>
            </AtField>

            <AtField :label="$t('Target Type')">
                <NSelect
                    size="large"
                    v-model:value="form.target_type"
                    :options="goalTargetTypes"
                />
            </AtField>

            <AtField :label="$t('Amount')" :errors="form.errors" field="amount">
                <LogerInput v-model="form.amount" type="number" min="0" step="0.01" />
            </AtField>

            <AtField :label="$t('Target date')" v-if="showDatePicker">
                <NDatePicker
                    type="date"
                    size="large"
                    v-model:value="form.frequency_date"
                    :is-date-disabled="(ts: number) => ts < Date.now() - 86400000"
                />
                <p v-if="monthlyHint" class="mt-2 text-sm font-medium text-primary">
                    {{ monthlyHint }}
                </p>
            </AtField>
        </section>

        <footer class="flex justify-end w-full px-6 py-4 space-x-3 bg-base">
            <AtButton type="secondary" rounded class="h-10" @click="close">
                {{ $t('Cancel') }}
            </AtButton>
            <LogerButton variant="inverse" :processing="form.processing" @click="submit">
                {{ $t('Create goal') }}
            </LogerButton>
        </footer>
    </Modal>
</template>
