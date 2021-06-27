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

                <div>
                    <at-button class="text-white bg-pink-400" @click="openModal()"> New Meal</at-button>
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
                    @close="isModalOpen=false"
                    :title="`Add a new meal to ${isModalOpen && getDayName(isModalOpen)}`"
                    :meals="meals"

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

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            PlanModal,
            AtWeekPager
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
                date: new Date(),
                week: null
            })

            const openModal = () => {
                state.isModalOpen = true
            }

            const getDayName = (date) => {
                return format(date, 'iiii')
            }

            const getDayMeals = (date) => {
                const isoDate = format(date, 'yyyy-MM-dd');
                return props.mealPlans.data.filter(mealPlan => mealPlan.date == isoDate);
            }

            return {
                ...toRefs(state),
                openModal,
                getDayName,
                getDayMeals
            }
        }
    }
</script>
