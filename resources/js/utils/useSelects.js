import { provide } from "vue";

export function useSelect() {
    const categoryOptions = (categories, isGroup = false, name = 'categoryOptions') => {
        if (!categories || !categories.map) return
        const options = categories.map(category => {
            category.type = isGroup && category.accounts ?  'group' : null;
            category.key = category.id;
            category.label = category.name;
            if (category.accounts) {
                category.children = category.accounts.map(account => {
                    account.value = account.id;
                    account.label = account.name;
                    return account;
                });
            }
            return category;
        })

        provide(name, options);
        return options;
    }

    return {
        categoryOptions
    }
}
