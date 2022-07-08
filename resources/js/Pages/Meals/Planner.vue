<template>
    <AppLayout title="Meal Planner">
        <div class="pl-6 pb-20 max-w-screen-2xl space-x-2 lg:pl-8">
            <div class="flex justify-between w-full mb-4">
                <div class="space-x-2 flex justify-end items-center ml-auto">
                    <AtDatePager
                        class="h-12 w-full border-none bg-slate-600 text-gray-200 ml-auto"
                        v-model="state.date"
                        v-model:dateSpan="state.dateSpan"
                        v-model:startDate="state.searchOptions.date.startDate"
                        v-model:endDate="state.searchOptions.date.endDate"
                        controlsClass="bg-transparent text-gray-200 hover:bg-slate-600"
                    />
                    <AtButton class="h-10 w-64 text-white bg-pink-400" @click="openRandomModal()" rounded> Random Meal </AtButton>
                    <AtButton class="h-10 w-52 text-white bg-pink-400" @click="toggleMode()" rounded>
                        {{ state.toggleBtnText }}
                    </AtButton>
                </div>
            </div>

            <div v-if="state.isGroceryList" class="py-5 overflow-hidden border rounded-md bg-slate-600">
                <div v-for="(ingredient, name) in ingredients" class="px-5 text-pink-500 cursor-pointer bg-slate-600">
                    {{name }} ({{ ingredient.quantity }}) {{ ingredient.unit }}
                </div>
            </div>

            <div v-else class="pt-5 overflow-hidden text-gray-200 border divide-y-2 rounded-md divide-slate-800 border-slate-800 bg-slate-600">
                <div v-for="day in state.dateSpan" :key="day" @click="openDayInModal(day)" class="px-5 py-4 cursor-pointer bg-slate-600">
                    {{ getDayName(day) }}

                    <div class="">
                        <div v-for="meal in getDayMeals(day)" :key="meal.id" class="font-bold text-pink-400">
                            {{ meal.dateable.name }}
                        </div>
                    </div>
                </div>
            </div>

            <PlanModal
                :show="state.isModalOpen"
                :closeable="true"
                :date="state.isModalOpen"
                :selected="state.selectedMealsOfDay"
                :title="`Add a new meal to ${isModalOpen && getDayName(isModalOpen)}`"
                :meals="meals"
                @close="state.isModalOpen=false"
                @saved="onSaved"
            />

            <RandomMealModal
                :show="state.isRandomModalOpen"
                :closeable="true"
                @close="state.isRandomModalOpen=false"
            />
        </div>
    </AppLayout>
</template>

<script setup>
    import { format, startOfDay } from "date-fns";
    import { AtButton, AtDatePager } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue';
    import PlanModal from '@/Components/PlanModal.vue';
    import { Inertia } from '@inertiajs/inertia';
    import { computed, nextTick, reactive, toRefs } from "vue";
    import RandomMealModal from '@/Components/RandomMealModal.vue';

    const props = defineProps({
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
    });

    const state = reactive({
        isModalOpen: false,
        isRandomModalOpen: false,
        searchOptions: {
            group: "",
            date: {
                startDate: null,
                endDate: null,
            }
        },
        date: startOfDay(props.serverSearchOptions?.date?.startDate || new Date()),
        dateSpan: null,
        mode: "week",
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

    const toggleMode = () => {
        Inertia.visit(`/meal-planner?${getMode(true)}`);
    }

    const openDayInModal = (day) => {
        state.selectedMealsOfDay = getDayMeals(day).map( item => ({
            ...item.dateable,
            schedule_id: item.id
        })
        );
        state.isModalOpen = day;
    }
</script>
