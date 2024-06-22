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

    const monthMovementVariance = computed(() => {
        return  (monthMovement.value /  lastMonth.value * 100.00).toFixed(2);
    });

    return {
        monthMovementVariance,
        monthMovement,
        thisMonth,
        lastMonth
    }
}
