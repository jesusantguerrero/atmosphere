import { Inertia } from "@inertiajs/inertia";
import { startOfDay } from "date-fns";

export const addPlan = (recipe) => {
    if (!recipe.id && !recipe.name) return
    Inertia.post(route('meals.addPlan'), {
        date: startOfDay(new Date()),
        meals: [recipe]
    });
}
