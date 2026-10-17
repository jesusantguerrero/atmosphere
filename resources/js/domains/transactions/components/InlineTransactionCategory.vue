<script setup lang="ts">
import { inject, ref } from 'vue';
import { NSelect } from 'naive-ui';
import axios from 'axios';

const props = defineProps<{ transaction: Record<string, any> }>();
const emit = defineEmits(['saved']);
const categories = inject<any[]>('categoryOptions', []);
const saving = ref(false);
const error = ref(false);
const selected = ref(props.transaction.category_id ?? null);
const save = async (categoryId: number): Promise<void> => {
    if (saving.value || !categoryId || categoryId === selected.value) return;
    saving.value = true;
    error.value = false;
    try {
        await axios.patch(`/finance/transaction-lines/${props.transaction.category_line_id}/category`, { category_id: categoryId });
        selected.value = categoryId;
        emit('saved');
    } catch {
        error.value = true;
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div class="mt-1 min-w-0" @click.stop>
        <NSelect v-if="transaction.category_line_id && !Number(transaction.has_splits) && !Number(transaction.is_transfer)" :value="selected" :options="categories" filterable size="small" :placeholder="$t('Choose category')" :aria-label="$t('Category')" :loading="saving" :disabled="saving" :consistent-menu-width="false" @update:value="save" />
        <span v-else class="text-xs text-body-1">{{ Number(transaction.has_splits) ? $t('Split transaction') : (transaction.category_name || (Number(transaction.is_transfer) ? $t('Transfer') : $t('Uncategorized'))) }}</span>
        <p v-if="error" role="alert" class="mt-1 text-xs text-error">{{ $t('Could not save category. Try again.') }}</p>
    </div>
</template>
