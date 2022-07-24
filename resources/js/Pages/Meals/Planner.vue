<template>
    <AppLayout title="Meal Planner">
        <template #header>
            <MealSectionNav>
                <template #actions>
                    <div class="flex items-center justify-end ml-auto space-x-2">
                        <AtDatePager
                            class="w-full h-12 ml-auto border-none bg-base-lvl-1 text-body"
                            v-model="state.date"
                            v-model:dateSpan="state.dateSpan"
                            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                            next-mode="week"
                        />
                        <AtButton class="w-64 h-10 text-white bg-primary" @click="openRandomModal()" rounded> Random Meal </AtButton>
                        <AtButton class="h-10 text-white w-52 bg-primary" @click="toggleMode()" rounded>
                            {{ state.toggleBtnText }}
                        </AtButton>
                    </div>
                </template>
            </MealSectionNav>
        </template>
        <MealTemplate class="mx-auto">
            <div class="pb-20 space-x-2">
                <div v-if="state.isGroceryList" class="py-5 overflow-hidden border rounded-md bg-base-lvl-1">
                    <div v-for="(ingredient, name) in ingredients" class="px-5 cursor-pointer text-primary bg-base-lvl-1">
                        {{name }} ({{ ingredient.quantity }}) {{ ingredient.unit }}
                    </div>
                </div>

                <div v-else class="pt-5 overflow-hidden border divide-y-2 rounded-md text-body divide-base border-base bg-base-lvl-3">
                    <div v-for="day in state.dateSpan" :key="day" @click="openDayInModal(day)" class="px-5 py-4 cursor-pointer bg-base-lvl-3">
                        {{ getDayName(day) }}

                        <div class="flex items-center mt-2 space-x-2">
                            <div v-for="mealType in pageProps.mealTypes" class="w-full">
                                <MealTypeCell
                                    v-model="recipe"
                                    :planned-meal="getDayMeals(day, mealType.id)"
                                    :meal-type="mealType"
                                    @submit="addPlan"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <RandomMealModal
                    :show="state.isRandomModalOpen"
                    :closeable="true"
                    @close="state.isRandomModalOpen=false"
                />
            </div>
        </MealTemplate>
    </AppLayout>
</template>

<script setup>
    import { format, startOfDay } from "date-fns";
    import { AtButton, AtDatePager } from "atmosphere-ui";
    import { useForm, usePage } from "@inertiajs/inertia-vue3";
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Inertia } from '@inertiajs/inertia';
    import { computed, reactive } from "vue";
    import RandomMealModal from '@/Components/RandomMealModal.vue';
    import MealTemplate from "@/Components/templates/MealTemplate.vue";
    import MealSectionNav from "@/Components/templates/MealSectionNav.vue";
    import MealTypeCell from "@/Components/molecules/MealTypeCell.vue";

    const pageProps = usePage().props;

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
        selectedDay: false,
        isRandomModalOpen: false,
        date: startOfDay(new Date()),
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

    const getDayMeals = (date, mealTypeId) => {
        const isoDate = format(date, 'yyyy-MM-dd');
        return props.mealPlans.data.find(mealPlan => {
            return mealPlan.date == isoDate && mealPlan.dateable.meal_type_id == mealTypeId
        });
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
        state.selectedDay = day;
    }

    const form = useForm({
        date: null,
        meals: []
    });

    const addPlan = (recipe) => {
        if (!recipe.id && !recipe.name) return
        form
        .transform(()=> ({
            date: startOfDay(state.selectedDay),
            meals: [recipe]
        }))
        .post(route('meals.addPlan'), {
            onSuccess: () => {
                form.id = "";
                form.name = ""
                form.reset()
            }
        })
    }
</script>
