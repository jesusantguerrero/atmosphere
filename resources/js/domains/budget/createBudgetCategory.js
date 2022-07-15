import { Inertia } from "@inertiajs/inertia";

export const createBudgetCategory = (categoryForm, parentCategoryId = null) => {
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
        }
    })
}
