import { router } from "@inertiajs/vue3";
import { nextTick } from "vue";

export const createBudgetCategory = (categoryForm: Record<string, any>, parentCategoryId?: number, callback?: () => {}) => {
    categoryForm.transform((formData: Record<string, string>) => ({
        ...formData,
        resource_type: "transactions",
        parent_id: parentCategoryId
    })).post("/budgets", {
        onSuccess() {
            router.reload({
                preserveScroll: true
            })
            categoryForm.reset();
            nextTick(() => {
                callback && callback()
            })
        }
    })
}
