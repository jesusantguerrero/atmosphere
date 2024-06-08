import { inject } from "vue";

export function useAccounts(tableName?: string, relationshipTable?: string, relationshipFields?: string[]) {
    const accountsOptions = inject("accountsOptions", []);

    return {
        accounts: accountsOptions
    }
}
