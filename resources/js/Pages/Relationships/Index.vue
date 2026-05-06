<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
import Modal from '@/Components/atoms/Modal.vue';

interface RelationshipReminder {
    id: number;
    name: string;
    last_date: string | null;
    avg_days_passed: number;
    days_until_next: number | null;
}

const props = defineProps<{
    relationships: RelationshipReminder[];
}>();

const isAddModalOpen = ref(false);

const addForm = useForm({
    name: '',
    avg_days_passed: 30,
});

const openAddModal = () => {
    addForm.reset();
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
};

const submitAdd = () => {
    addForm.post('/relationships', {
        onSuccess: () => closeAddModal(),
    });
};

const markingIds = ref<Set<number>>(new Set());

const markAsOccurred = (reminder: RelationshipReminder) => {
    markingIds.value.add(reminder.id);
    router.post(
        `/relationships/${reminder.id}/mark-as-occurred`,
        {},
        {
            onFinish: () => markingIds.value.delete(reminder.id),
        }
    );
};

const isMarking = (id: number) => markingIds.value.has(id);

const daysAgoLabel = (lastDate: string | null): string => {
    if (!lastDate) {
        return 'Never';
    }
    const days = Math.floor(
        (Date.now() - new Date(lastDate).getTime()) / 86_400_000
    );
    if (days === 0) {
        return 'Today';
    }
    if (days === 1) {
        return 'Yesterday';
    }
    return `${days} days ago`;
};

const badgeClass = (daysUntilNext: number | null): string => {
    if (daysUntilNext === null) {
        return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
    if (daysUntilNext >= 0) {
        return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300';
    }
    if (daysUntilNext >= -3) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
    }
    return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
};

const badgeLabel = (reminder: RelationshipReminder): string => {
    const d = reminder.days_until_next;
    if (d === null) {
        return 'New';
    }
    if (d > 0) {
        return `Due in ${d}d`;
    }
    if (d === 0) {
        return 'Due today';
    }
    return `${Math.abs(d)}d overdue`;
};
</script>

<template>
    <AppLayout title="Relationships">
        <main class="px-5 mx-auto mt-12 max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-body-1">Relationship Reminders</h2>
                <LogerButton variant="secondary" class="flex" @click="openAddModal">
                    <IMdiPlus class="mr-2" />
                    Add reminder
                </LogerButton>
            </div>

            <section v-if="relationships.length" class="space-y-3 mb-20">
                <div
                    v-for="reminder in relationships"
                    :key="reminder.id"
                    class="flex items-center justify-between p-4 border rounded-lg shadow-sm bg-base-lvl-3 hover:bg-base-lvl-2 transition"
                >
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="min-w-0">
                            <p class="font-semibold text-body-1 truncate">{{ reminder.name }}</p>
                            <p class="text-sm text-body-2 mt-0.5">
                                Last: {{ daysAgoLabel(reminder.last_date) }}
                                <span class="text-body-2/60 ml-1">(every ~{{ reminder.avg_days_passed }}d)</span>
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full"
                            :class="badgeClass(reminder.days_until_next)"
                        >
                            {{ badgeLabel(reminder) }}
                        </span>
                    </div>

                    <LogerButton
                        variant="inverse"
                        class="ml-4 shrink-0 text-sm"
                        :processing="isMarking(reminder.id)"
                        @click="markAsOccurred(reminder)"
                    >
                        Just saw them
                    </LogerButton>
                </div>
            </section>

            <div v-else class="flex flex-col items-center justify-center py-24 rounded-lg bg-base-lvl-3 mb-20">
                <IMdiAccountHeartOutline class="text-4xl text-body-2 mb-4" />
                <h3 class="text-lg font-bold text-body-1 mb-2">No relationship reminders yet</h3>
                <p class="text-body-2 max-w-md text-center mb-6">
                    Track when you last had a date with Hope, called your mom, or caught up with a friend.
                    We'll nudge you when it's been longer than usual.
                </p>
                <LogerButton variant="inverse" @click="openAddModal">
                    Add your first reminder
                </LogerButton>
            </div>
        </main>

        <Modal :show="isAddModalOpen" max-width="md" @close="closeAddModal">
            <div class="p-6">
                <h3 class="text-lg font-bold text-body-1 mb-4">Add relationship reminder</h3>

                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-body-1 mb-1">Name</label>
                        <LogerInput
                            v-model="addForm.name"
                            placeholder="e.g. Hope, Mom, Alex"
                            class="w-full"
                        />
                        <p v-if="addForm.errors.name" class="mt-1 text-sm text-red-600">{{ addForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-body-1 mb-1">Remind every (days)</label>
                        <LogerInput
                            v-model="addForm.avg_days_passed"
                            type="number"
                            placeholder="30"
                            class="w-full"
                        />
                        <p v-if="addForm.errors.avg_days_passed" class="mt-1 text-sm text-red-600">{{ addForm.errors.avg_days_passed }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <LogerButton variant="neutral" type="button" @click="closeAddModal">Cancel</LogerButton>
                        <LogerButton variant="secondary" type="submit" :processing="addForm.processing">Save</LogerButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
