<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <h2 class="text-xl font-semibold leading-tight text-primary">
                    Goals
                </h2>
                <span>There a total of {{ goals.data.length || 0 }} goals</span>

                </div>

                <div>
                    <AtButton class="text-white bg-primary" @click="$inertia.visit(route('goals.create'))">
                        Create goal
                    </AtButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto space-y-4 max-w-7xl">
                <div v-for="goal in goals.data" class="px-5 py-3 space-y-4 border rounded-md bg-base-lvl-1">
                    <div class="flex">
                        <div class="w-10 h-10 bg-gray-700 rounded-full"></div>
                        <div class="ml-2">
                            <h4 class="text-xl font-bold">{{ goal.name }}</h4>
                            <p class="font-bold text-gray-500"> {{ goal.due_date }}</p>
                        </div>
                    </div>
                    <div>
                        <n-progress :percentage="getPercentage(goal.amount, goal.target)" :stroke-width="1"  border-radius="0" />
                    </div>
                    <div class="flex justify-between goal_foot">
                        <span class="font-bold text-green-500"> Saved: {{ formatMoney(goal.amount) }}</span>
                        <span class="font-bold text-gray-500"> Goal: {{ formatMoney(goal.target) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import { NProgress } from "naive-ui"
    import AppLayout from '@/Components/templates/AppLayout.vue';
    import formatMoney from '@/utils/formatMoney';

    const getPercentage = (actual, target) => {
        const percentage = Number(actual||0) / Number(target||0) * 100
        return percentage.toFixed(2);
    }

    defineProps({
        goals: {
            type: Array,
            required: true
        }
    });
</script>
