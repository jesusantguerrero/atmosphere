<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import LogerButton from '@/Components/atoms/LogerButton.vue';

type Menu = {
    id: number;
    name: string;
    description: string | null;
    meal_plans_count: number;
};

const props = defineProps<{ menu: Menu }>();

const isDatePickerOpen = ref(false);
const targetDate = ref('');
const isDeleting = ref(false);

const loadForm = useForm({
    target_start_date: '',
});

const openDatePicker = () => {
    isDatePickerOpen.value = true;
};

const cancelLoad = () => {
    isDatePickerOpen.value = false;
    targetDate.value = '';
};

const useTemplate = () => {
    if (!targetDate.value) {
        return;
    }
    loadForm.target_start_date = targetDate.value;
    loadForm.post(route('meals.menus.load', props.menu.id), {
        onSuccess: () => {
            isDatePickerOpen.value = false;
            targetDate.value = '';
        },
    });
};

const deleteMenu = () => {
    if (!confirm(`Delete template "${props.menu.name}"? The planned meals will be kept.`)) {
        return;
    }
    isDeleting.value = true;
    router.delete(route('meals.menus.destroy', props.menu.id), {
        onFinish: () => { isDeleting.value = false; },
    });
};
</script>

<template>
    <article class="flex flex-col overflow-hidden border rounded-lg bg-base-lvl-2 border-base text-body">
        <div class="flex-1 px-5 py-4">
            <h3 class="text-sm font-semibold text-body capitalize">{{ menu.name }}</h3>
            <p v-if="menu.description" class="mt-1 text-xs text-body-1 opacity-70 line-clamp-2">
                {{ menu.description }}
            </p>
            <p class="mt-2 text-xs text-body-1 opacity-60">
                {{ menu.meal_plans_count }} meal{{ menu.meal_plans_count !== 1 ? 's' : '' }}
            </p>
        </div>

        <div v-if="isDatePickerOpen" class="px-5 pb-4 space-y-2 border-t border-base">
            <label class="block mt-3 text-xs font-medium text-body">Start date for this week</label>
            <input
                v-model="targetDate"
                type="date"
                class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <div class="flex gap-2">
                <LogerButton
                    variant="secondary"
                    class="flex-1 h-8 text-xs"
                    :processing="loadForm.processing"
                    :disabled="!targetDate"
                    @click="useTemplate"
                >
                    Confirm
                </LogerButton>
                <LogerButton variant="neutral" class="h-8 text-xs" @click="cancelLoad">
                    Cancel
                </LogerButton>
            </div>
        </div>

        <div v-else class="flex items-center gap-2 px-5 pb-4 border-t border-base pt-3">
            <LogerButton
                variant="secondary"
                class="flex-1 h-8 text-xs"
                @click="openDatePicker"
            >
                Use this template
            </LogerButton>
            <button
                type="button"
                class="p-1.5 text-error opacity-60 hover:opacity-100 transition-opacity"
                :disabled="isDeleting"
                title="Delete template"
                @click="deleteMenu"
            >
                <IMdiTrashCanOutline class="w-4 h-4" />
            </button>
        </div>
    </article>
</template>
