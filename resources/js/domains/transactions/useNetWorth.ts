import { computed, Ref } from "vue";

export interface INetWorthEntry { assets: string, debts: string }
export const useNetWorth = (netWorthData: Ref<INetWorthEntry[]>) => {

    const getEntryBalance = (monthEntry: INetWorthEntry = {  assets: "0", debts: "0"}) => {
        return parseFloat(monthEntry.assets) + parseFloat(monthEntry.debts);
    };

    const lastMonth = computed(() => {
        return getEntryBalance(netWorthData.value?.at?.(-2))
    });

    const thisMonth = computed(() => {
        return getEntryBalance(netWorthData?.value.at?.(-1))
    });
    const monthMovement = computed(() => {
        return  thisMonth.value - lastMonth.value;
    });

    // Returns null when there's no prior-month baseline (avoids the
    // "Infinity%" render that fires for brand-new accounts and the
    // demo seed). Consumers should v-if on the value before showing
    // a "% change" pill.
    const monthMovementVariance = computed<string | null>(() => {
        if (!lastMonth.value || !Number.isFinite(lastMonth.value)) {
            return null;
        }
        return (monthMovement.value / lastMonth.value * 100.00).toFixed(2);
    });

    return {
        monthMovementVariance,
        monthMovement,
        thisMonth,
        lastMonth
    }
}
