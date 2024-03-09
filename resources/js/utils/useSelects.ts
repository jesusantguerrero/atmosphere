import { cloneDeep } from "lodash";
import { provide } from "vue";

export function useSelect() {
    const categoryOptions = (categoriesData: any, groupName = 'subcategories', name = 'categoryOptions') => {
        if (!categoriesData || !categoriesData.map) return
        const categories = cloneDeep(categoriesData)
        const options = categories.map((category: any) => {
            if (category) {
                category.type = groupName && category[groupName] ?  'group' : null;
                category.key = category.id;
                category.value = category.id;
                category.label = category.name;
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
