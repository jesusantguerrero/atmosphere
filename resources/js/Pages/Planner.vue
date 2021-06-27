<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <h2 class="text-xl font-semibold leading-tight text-pink-500">
                    Meal Planner
                </h2>
                <span></span>

                </div>

                <div class="space-x-2">
                    <at-button class="text-white bg-pink-400" @click="openRandomModal()"> Random Meal </at-button>
                    <at-button class="text-white bg-pink-400" @click="openModal()"> Get grocery list</at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6">
                <at-week-pager
                    class="h-12 bg-white"
                    v-model="date"
                    v-model:week="week"
                    next-mode="week"
                />

                <div class="pt-5 overflow-hidden divide-y-2 rounded-md">
                    <div v-for="day in week" :key="day" @click="isModalOpen=day" class="px-5 py-4 bg-white cursor-pointer">
                        {{ getDayName(day) }}

                        <div class="">
                            <div v-for="meal in getDayMeals(day)" :key="meal.id" class="font-bold text-pink-500">
                                {{ meal.dateable.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <plan-modal
                    :show="isModalOpen"
                    :closeable="true"
                    :date="isModalOpen"
                    :title="`Add a new meal to ${isModalOpen && getDayName(isModalOpen)}`"
                    :meals="meals"
                    @close="isModalOpen=false"
                    @saved="onSaved"
                />

                <random-meal-modal
                    :show="isRandomModalOpen"
                    :closeable="true"
                    @close="isRandomModalOpen=false"
                />
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout';
    import { format } from "date-fns";
    import { AtButton, AtWeekPager } from "atmosphere-ui/dist/atmosphere-ui.es";
    import MealSection from '@/Components/Meal';
    import { reactive, toRefs } from '@vue/reactivity';
    import PlanModal from '../Components/PlanModal.vue';
    import { Inertia } from '@inertiajs/inertia';
    import { nextTick } from '@vue/runtime-core';
    import RandomMealModal from '../Components/RandomMealModal.vue';

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            PlanModal,
            AtWeekPager,
            RandomMealModal
        },
        props: {
            mealPlans: {
                type: Object,
                required: true
            },
            meals: {
                type: Array,
                required: true
            }
        },
        setup(props) {
            const state = reactive({
                isModalOpen: false,
                isRandomModalOpen: false,
                date: new Date(),
                week: null
            })

            const openRandomModal = () => {
                state.isRandomModalOpen = true
            }

            const getDayName = (date) => {
                return format(date, 'iiii')
            }

            const getDayMeals = (date) => {
                const isoDate = format(date, 'yyyy-MM-dd');
                return props.mealPlans.data.filter(mealPlan => mealPlan.date == isoDate);
            }

            const onSaved = () => {
                nextTick(() => {
                    Inertia.reload();
                })
            }

            return {
                ...toRefs(state),
                openRandomModal,
                getDayName,
                getDayMeals,
                onSaved
            }
        }
    }
</script>
