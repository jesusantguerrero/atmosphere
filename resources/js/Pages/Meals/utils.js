import { router } from "@inertiajs/vue3";
import { startOfDay } from "date-fns";

export const addPlan = (recipe) => {
    if (!recipe.id && !recipe.name) return
    router.post(route('meals.addPlan'), {
        date: startOfDay(new Date()),
        meals: [recipe]
    });
}
