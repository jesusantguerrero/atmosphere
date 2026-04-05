<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { useCollapse } from '@/composables/useCollapse';

const DISMISSED_KEY = 'loger-onboarding-dismissed';

const props = defineProps<{
    steps: any[];
    percentage?: number;
}>();

const isDismissed = ref(false);
const collapsable = ref<HTMLElement>();

const isComplete = computed(() => props.percentage === 100);

const { isCollapsed, toggleCollapse, icon } = useCollapse(collapsable, isComplete.value);

onMounted(() => {
    isDismissed.value = Boolean(localStorage.getItem(DISMISSED_KEY));
});

const dismiss = () => {
    localStorage.setItem(DISMISSED_KEY, '1');
    isDismissed.value = true;
};
</script>

<template>
    <div
        v-if="!isDismissed"
        class="space-y-2 border border-transparent rounded bg-base-lvl-3 shadow-xl"
        ref="collapsable"
        :class="{ 'pb-4': isCollapsed }"
    >
        <header
            class="flex items-center justify-between px-4 py-2 cursor-pointer"
            @click="toggleCollapse()"
        >
            <SectionTitle type="secondary">Onboarding</SectionTitle>
            <div class="flex items-center gap-3">
                <span v-if="percentage !== undefined" class="text-xs font-semibold text-body-1/60">
                    {{ percentage }}%
                </span>
                <i :class="icon" />
            </div>
        </header>

        <!-- Progress bar -->
        <div v-if="percentage !== undefined && !isCollapsed" class="px-4">
            <div class="w-full h-1.5 rounded-full bg-base-lvl-2 overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="isComplete ? 'bg-green-500' : 'bg-primary'"
                    :style="{ width: `${percentage}%` }"
                />
            </div>
        </div>

        <div v-if="!isCollapsed" class="space-y-2 px-4">
            <div
                v-for="step in steps"
                :key="step.name"
                class="flex px-3 py-3 space-x-3 border rounded-md border-base-lvl-2 group flex-nowrap bg-base-lvl-3"
                :class="{ 'opacity-50': step.complete }"
            >
                <div
                    class="flex items-center justify-center w-9 h-9 flex-shrink-0 transition-all border rounded-md"
                    :class="step.complete
                        ? 'border-green-400 text-green-500 bg-green-50 dark:bg-green-950'
                        : 'border-primary text-primary shadow-primary/20'"
                >
                    <i :class="step.complete ? 'fas fa-check' : step.icon" />
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-body-1">{{ step.label }}</p>
                    <p class="text-xs text-body-1/60 mt-0.5">{{ step.description }}</p>
                    <Link
                        v-if="!step.complete && step.link"
                        :href="step.link"
                        class="inline-flex items-center gap-1 mt-2 px-3 py-1 text-xs font-semibold rounded-md bg-primary text-white hover:bg-primary/80 transition-colors"
                    >
                        {{ step.cta }}
                        <i class="fas fa-arrow-right text-[10px]" />
                    </Link>
                    <span v-else-if="step.complete" class="mt-1 inline-block text-xs text-green-600 font-medium">
                        Done
                    </span>
                </div>
            </div>

            <div v-if="isComplete" class="flex justify-end pt-1">
                <button
                    class="text-xs text-body-1/40 hover:text-body-1/70 transition-colors"
                    @click.stop="dismiss"
                >
                    Dismiss widget
                </button>
            </div>
        </div>
    </div>
</template>
