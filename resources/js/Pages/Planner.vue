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
                    <AtButton class="text-white bg-pink-400" @click="openRandomModal()"> Random Meal </AtButton>
                    <AtButton class="text-white bg-pink-400" @click="toggleMode()">
                        {{ toggleBtnText }}
                    </AtButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6">
                <AtDatePager
                    class="h-12 mb-10 bg-white"
                    v-model="date"
                    v-model:dateSpan="week"
                    next-mode="week"
                />

                <div v-if="isGroceryList" class="py-5 overflow-hidden bg-white border rounded-md">
                    <div v-for="(ingredient, name) in ingredients" class="px-5 text-pink-500 bg-white cursor-pointer">
                        {{name }} ({{ ingredient.quantity }}) {{ ingredient.unit }}
                    </div>
                </div>

                <div v-else class="pt-5 overflow-hidden bg-white border divide-y-2 rounded-md">
                    <div v-for="day in week" :key="day" @click="openDayInModal(day)" class="px-5 py-4 bg-white cursor-pointer">
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
                    :selected="selectedMealsOfDay"
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
    import { AtButton, AtDatePager } from "atmosphere-ui";
    import MealSection from '@/Components/Meal';
    import PlanModal from '../Components/PlanModal.vue';
    import { Inertia } from '@inertiajs/inertia';
    import { computed, nextTick, reactive, toRefs, onMounted } from "vue";
    import RandomMealModal from '../Components/RandomMealModal.vue';

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            PlanModal,
            AtDatePager,
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
            },
            ingredients: {
                type: Object,
                default() {
                    return {}
                }
            },
            mode: {
                type: String,
                default: ''
            }
        },
        setup(props) {
            const state = reactive({
                isModalOpen: false,
                isRandomModalOpen: false,
                date: new Date(),
                week: null,
                selectedMealsOfDay: [],
                isGroceryList: computed(() => {
                    return props.mode == 'grocery-list';
                }),
                toggleBtnText: computed(() => {
                    return state.isGroceryList ? 'Meal planner' : 'Grocery List';
                })
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

            const getMode = (toggled) => {
                if (toggled) {
                    return state.isGroceryList ? '' : 'mode=grocery-list';
                } else {
                    return props.mode ? `mode=${props.mode}` : '';
                }
            }

            const getDates = () => {
                const startDate = state.week[0];
                const endDate = state.week[6];
                return `filter[date]=${format(startDate, 'yyyy-MM-dd')}~${format(endDate, 'yyyy-MM-dd')}`;
            }

            const toggleMode = () => {
                Inertia.visit(`/meal-planner?${getMode(true)}`);
            }

            const onWeekChanged = () => {
                const params = [getMode(), getDates()].join('&');
                Inertia.visit(`/meal-planner?${params}`);
            };

            const openDayInModal = (day) => {
                state.selectedMealsOfDay = getDayMeals(day).map( item => ({
                    ...item.dateable,
                    schedule_id: item.id
                })
                );
                state.isModalOpen = day;
            }

            return {
                ...toRefs(state),
                openRandomModal,
                getDayName,
                getDayMeals,
                onSaved,
                toggleMode,
                onWeekChanged,
                openDayInModal,
            }
        }
    }
</script>
