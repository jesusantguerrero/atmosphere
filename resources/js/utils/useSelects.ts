import { cloneDeep } from "lodash";
import { provide } from "vue";

// Reserved budget category names are seeded in English (BudgetReservedNames).
// They surface in every category picker (e.g. the income transaction modal), so
// translate them for display only — keep name/value/display_id untouched since
// all logic keys off those, not the visible label. Locale via window.logerLocale
// (same convention as the date helpers in utils/index.ts).
const RESERVED_CATEGORY_LABELS: Record<string, Record<string, string>> = {
    "Ready to Assign": { es: "Por asignar" },
    "Inflow": { es: "Ingresos" },
};

const displayCategoryLabel = (name: string): string => {
    const loc = String((typeof window !== "undefined" && (window as any).logerLocale) || "en")
        .toLowerCase()
        .slice(0, 2);
    return RESERVED_CATEGORY_LABELS[name]?.[loc] ?? name;
};

export function useSelect() {
    const categoryOptions = (categoriesData: any, groupName = 'subcategories', name = 'categoryOptions') => {
        if (!categoriesData || !categoriesData.map) return
        const categories = cloneDeep(categoriesData)
        const options = categories.map((category: any) => {
            if (category) {
                category.type = groupName && category[groupName] ?  'group' : null;
                category.key = category.id;
                category.value = category.id;
                category.label = displayCategoryLabel(category.name);
                if (category[groupName]) {
                    category.children = categoryOptions(category[groupName], false, false);
                }
            }
            return category;
        })
        if (name) {
            provide(name, options);
        }
        return options;
    }

    return {
        categoryOptions
    }
}
