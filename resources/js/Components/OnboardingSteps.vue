<template>
<div class="space-y-2 rounded border border-transparent" ref="collapsable" :class="{'bg-base-lvl-3 shadow-xl border-base rounded border': isCollapsed}">
    <header class="px-4 py-2 flex justify-between cursor-pointer" @click="toggleCollapse()">
        <SectionTitle type="secondary"> Onboarding </SectionTitle>
        <i :class="icon" />
    </header>
    <div v-if="!isCollapsed" class="space-y-2">
        <div v-for="step in steps" class="border cursor-pointer group flex flex-nowrap space-x-4 bg-base-lvl-3 rounded-md p-4 shadow-xl">
            <div class="h-10 w-10 border-slate-300 transition-all group-hover:border-primary group-hover:text-primary text-slate-300 justify-center rounded-md border flex items-center">
                <i :class="step.icon" />
            </div>
            <div class="ml-4 w-full">
                <h1 class="text-body-1 font-bold"> {{ step.label }}</h1>
                <p class="text-body">
                    {{ step.description }}
                </p>
                <Link :href="step.action.link" class="mt-2 text-sm text-body-1 group-hover:text-primary/80"> {{ step.action.label }}</Link>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>
import { ref } from 'vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { useCollapse } from '@/composables/useCollapse';
import { Link } from '@inertiajs/inertia-vue3';

const steps = [
    {
        icon: 'fas fa-envelope-open-text',
        name: 'confirmEmail',
        label: 'Step 1: confirm your email',
        description: 'Validate your email address',
        action: {
            link: '/user/profile',
            label: 'Resend confirmation'
        }
    }, {
        icon: 'fas fa-credit-card',
        name: 'addAccounts',
        label: 'Step 2: Add accounts',
        description: 'Create your accounts',
        action: {
            link: '/finance',
            label: 'Go to finance/accounts'
        }
    }, {
        icon: 'fas fa-tags',
        name: 'AddCategories',
        label: 'Step 3: Add budget categories',
        description: 'Structure your budgets with category groups and categories',
        action: {
            link: '/budgets',
            label: 'Go to finance/budget'
        }
    }, {
        icon: 'fas fa-calendar-alt',
        name: 'AddMealPlan',
        label: 'Step 4: Add meal plan',
        description: 'Organize your meals',
        action: {
            link: '/meal-planner',
            label: 'Go to meal/plan'
        }
    }
]

const collapsable = ref();
const { isCollapsed, toggleCollapse, icon } = useCollapse(collapsable, false);
</script>
