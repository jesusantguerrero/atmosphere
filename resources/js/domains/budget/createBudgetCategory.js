import { Inertia } from "@inertiajs/inertia";
import { nextTick } from "vue";

export const createBudgetCategory = (categoryForm, parentCategoryId = null, callback) => {
    categoryForm.transform((formData) => ({
        ...formData,
        resource_type: "transactions",
        parent_id: parentCategoryId
    })).post("/budgets", {
        onSuccess() {
            Inertia.reload({
                preserveScroll: true
            })
            categoryForm.reset();
            nextTick(() => {
                callback && callback()
            })
        }
    })
}
