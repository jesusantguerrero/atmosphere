<script setup lang="ts">
    import { format, startOfDay } from "date-fns";
    // @ts-ignore
    import { AtDatePager } from "atmosphere-ui";
    import { useI18n } from "vue-i18n";
    import { useForm, usePage } from "@inertiajs/vue3";
    import { router } from '@inertiajs/vue3';
    import { computed, reactive, ref } from "vue";
    import axios from "axios";

    import AppLayout from '@/Components/templates/AppLayout.vue';
    import LogerButton from "@/Components/atoms/LogerButton.vue";

    import RandomMealModal from '@/domains/meal/components/RandomMealModal.vue';
    import MealTemplate from "@/domains/meal/components/MealTemplate.vue";
    import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";
    import MealTypeCell from "@/domains/meal/components/MealTypeCell.vue";
    import MealMenuModal from "@/domains/meal/components/MealMenuModal.vue";

    const { t } = useI18n();
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
            return state.isGroceryList ? t('Meal Planner') : t('Grocery List');
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
        return props.mealPlans.find(mealPlan => {
            return mealPlan.date == isoDate && mealPlan.dateable?.meal_type_id == mealTypeId
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
        router.visit(`/meal-planner?${getMode(true)}`);
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
            preserveScroll: true,
            onSuccess: () => {
                form.id = "";
                form.name = ""
                form.reset()
            }
        })
    }

    const onToggleLike = (plannedMeal) => {
        plannedMeal.is_liked = !Boolean(plannedMeal.is_liked);
        router.put(route('meal-planner.update', plannedMeal), plannedMeal, {
            onSuccess() {
                router.reload({
                    preserveScroll: true,
                })
            }
        })
    }

    const onRemoved = (plannedMeal) => {
        plannedMeal.is_liked = !Boolean(plannedMeal.is_liked);
        router.delete(route('meal-planner.destroy', plannedMeal), plannedMeal, {
            onSuccess() {
                router.reload({
                    preserveScroll: true,
                })
            }
        })
    }

    const isGenerating = ref(false);

    const generateShoppingList = () => {
        if (!state.dateSpan || state.dateSpan.length === 0) return;

        const startDate = format(state.dateSpan[0], 'yyyy-MM-dd');
        const endDate = format(state.dateSpan[state.dateSpan.length - 1], 'yyyy-MM-dd');

        isGenerating.value = true;
        router.post(
            route('meals.shoppingList.generate'),
            { start_date: startDate, end_date: endDate },
            {
                onFinish: () => { isGenerating.value = false; },
            }
        );
    };

    // Menu modal state
    type ModalMode = 'save' | 'load';
    const menuModalMode = ref<ModalMode>('save');
    const isMenuModalOpen = ref(false);
    const savedMenus = ref([]);

    const currentWeekStartDate = computed(() => {
        if (!state.dateSpan || state.dateSpan.length === 0) return '';
        return format(state.dateSpan[0], 'yyyy-MM-dd');
    });

    const currentWeekEndDate = computed(() => {
        if (!state.dateSpan || state.dateSpan.length === 0) return '';
        return format(state.dateSpan[state.dateSpan.length - 1], 'yyyy-MM-dd');
    });

    const openSaveMenuModal = () => {
        menuModalMode.value = 'save';
        isMenuModalOpen.value = true;
    };

    const openLoadMenuModal = async () => {
        menuModalMode.value = 'load';
        const { data } = await axios.get(route('meals.menus.index'));
        savedMenus.value = data;
        isMenuModalOpen.value = true;
    };

    const closeMenuModal = () => {
        isMenuModalOpen.value = false;
    };
</script>


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
                        <LogerButton variant="neutral" class="h-10 text-sm" @click="openSaveMenuModal">
                            {{ $t('Save as Menu') }}
                        </LogerButton>
                        <LogerButton variant="neutral" class="h-10 text-sm" @click="openLoadMenuModal">
                            {{ $t('Load Menu') }}
                        </LogerButton>
                        <LogerButton variant="inverse" class="w-64 h-10" @click="openRandomModal()">
                            {{ $t('Random Meal') }}
                        </LogerButton>
                        <LogerButton class="h-10 w-52" variant="secondary" @click="toggleMode()">
                            {{ state.toggleBtnText }}
                        </LogerButton>
                    </div>
                </template>
            </MealSectionNav>
        </template>
        <MealTemplate class="mx-auto">
            <div class="pb-20 space-x-2">
                <div v-if="state.isGroceryList" class="py-5 overflow-hidden border rounded-md bg-base-lvl-3">
                    <div class="flex items-center justify-between px-5 pb-4 border-b border-base">
                        <p class="text-sm text-body-1 font-semibold">Ingredients this week</p>
                        <LogerButton
                            variant="secondary"
                            class="h-8 text-sm"
                            :processing="isGenerating"
                            @click="generateShoppingList"
                        >
                            Add to shopping list
                        </LogerButton>
                    </div>
                    <div v-for="(ingredient, name) in ingredients" :key="ingredient.id" class="px-5 pt-3 cursor-pointer text-primary">
                        {{ name }} ({{ ingredient.quantity }}) {{ ingredient.unit }}
                    </div>
                    <p v-if="!ingredients || Object.keys(ingredients).length === 0" class="px-5 pt-4 text-sm text-body-1 opacity-60">
                        No meals planned for this period.
                    </p>
                </div>

                <div v-else class="pt-5 overflow-hidden border divide-y-2 rounded-md text-body divide-base border-base bg-base-lvl-3">
                    <div v-for="day in state.dateSpan" :key="day" @click="openDayInModal(day)" class="px-5 py-4 cursor-pointer bg-base-lvl-3">
                        {{ getDayName(day) }}

                        <div class="items-center mt-2 md:flex md:space-x-2">
                            <div v-for="mealType in pageProps.mealTypes" class="w-full" :key="`${mealType.id}-${day}`">
                                <MealTypeCell
                                    v-model="recipe"
                                    :planned-meal="getDayMeals(day, mealType.id)"
                                    :meal-type="mealType"
                                    @submit="addPlan"
                                    @toggle-like="onToggleLike"
                                    @removed="onRemoved"
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

                <MealMenuModal
                    :show="isMenuModalOpen"
                    :mode="menuModalMode"
                    :menus="savedMenus"
                    :start-date="currentWeekStartDate"
                    :end-date="currentWeekEndDate"
                    @close="closeMenuModal"
                />
            </div>
        </MealTemplate>
    </AppLayout>
</template>
