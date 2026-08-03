import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";

// Single source of truth for the team's accounts on the client. The server
// seeds it (AppLayout syncs it from the shared `accounts` Inertia prop on every
// navigation) and any place that mutates accounts — e.g. the Add/Edit account
// modal — calls refresh() so every consumer (the right-rail Accounts panel, the
// dashboard list, pickers) updates without a manual page reload.
export const useAccountsStore = defineStore("accounts", () => {
    const accounts = ref<any[]>([]);

    const setAccounts = (list: any[]): void => {
        accounts.value = Array.isArray(list) ? list : [];
    };

    // Re-pull the canonical list from the API. Used after create/edit/close so
    // the change is reflected everywhere immediately.
    const refresh = async (): Promise<void> => {
        const { data } = await axios.get("/api/accounts");
        setAccounts(data);
    };

    return { accounts, setAccounts, refresh };
});
