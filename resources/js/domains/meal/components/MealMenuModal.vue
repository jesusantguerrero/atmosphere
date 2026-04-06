<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/atoms/Modal.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

type Menu = {
    id: number;
    name: string;
    meal_plans_count: number;
};

type ModalMode = 'save' | 'load';

const props = defineProps<{
    show: boolean;
    mode: ModalMode;
    menus?: Menu[];
    startDate: string;
    endDate: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const saveForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

const isDeleting = ref<number | null>(null);

const close = () => {
    saveForm.reset();
    emit('close');
};

const submitSave = () => {
    saveForm.start_date = props.startDate;
    saveForm.end_date = props.endDate;
    saveForm.post(route('meals.menus.store'), {
        onSuccess: close,
    });
};

const loadMenu = (menu: Menu) => {
    router.post(
        route('meals.menus.load', menu.id),
        { target_start_date: props.startDate },
        { onSuccess: close }
    );
};

const deleteMenu = (menu: Menu) => {
    if (!confirm(`Delete menu "${menu.name}"? The planned meals will be kept.`)) {
        return;
    }
    isDeleting.value = menu.id;
    router.delete(route('meals.menus.destroy', menu.id), {
        onFinish: () => { isDeleting.value = null; },
    });
};

const title = computed(() => props.mode === 'save' ? 'Save Week as Menu' : 'Load a Saved Menu');
</script>

<template>
    <Modal :show="show" :closeable="true" max-width="lg" @close="close">
        <div class="px-6 py-5">
            <h2 class="text-lg font-semibold text-body">{{ title }}</h2>

            <!-- Save mode -->
            <template v-if="mode === 'save'">
                <p class="mt-1 text-sm text-body-1 opacity-70">
                    Saves all meals in the current week as a reusable menu template.
                </p>
                <div class="mt-4">
                    <label class="block mb-1 text-sm font-medium text-body">Menu name</label>
                    <input
                        v-model="saveForm.name"
                        type="text"
                        placeholder="e.g. Healthy Week"
                        class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-2 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
                        @keydown.enter.prevent="submitSave"
                    />
                    <p v-if="saveForm.errors.name" class="mt-1 text-xs text-error">
                        {{ saveForm.errors.name }}
                    </p>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <LogerButton variant="neutral" class="h-9 text-sm" @click="close">
                        Cancel
                    </LogerButton>
                    <LogerButton
                        variant="secondary"
                        class="h-9 text-sm"
                        :processing="saveForm.processing"
                        @click="submitSave"
                    >
                        Save Menu
                    </LogerButton>
                </div>
            </template>

            <!-- Load mode -->
            <template v-else>
                <p class="mt-1 text-sm text-body-1 opacity-70">
                    Select a menu to load into the current week.
                </p>
                <div class="mt-4 space-y-2 max-h-72 overflow-y-auto">
                    <div
                        v-for="menu in menus"
                        :key="menu.id"
                        class="flex items-center justify-between px-4 py-3 border rounded-md bg-base-lvl-2 border-base"
                    >
                        <div>
                            <p class="text-sm font-medium text-body">{{ menu.name }}</p>
                            <p class="text-xs text-body-1 opacity-60">
                                {{ menu.meal_plans_count }} meal{{ menu.meal_plans_count !== 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <LogerButton
                                variant="inverse"
                                class="h-8 text-xs"
                                @click="loadMenu(menu)"
                            >
                                Load
                            </LogerButton>
                            <button
                                class="p-1 text-error opacity-60 hover:opacity-100 transition-opacity"
                                :disabled="isDeleting === menu.id"
                                title="Delete menu"
                                @click="deleteMenu(menu)"
                            >
                                <IMdiTrashCanOutline class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <p v-if="!menus || menus.length === 0" class="py-4 text-sm text-center text-body-1 opacity-60">
                        No saved menus yet.
                    </p>
                </div>
                <div class="flex justify-end mt-4">
                    <LogerButton variant="neutral" class="h-9 text-sm" @click="close">
                        Close
                    </LogerButton>
                </div>
            </template>
        </div>
    </Modal>
</template>
