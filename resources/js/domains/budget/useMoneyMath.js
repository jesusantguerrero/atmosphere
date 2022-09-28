import { dinero, add as dineroAdd } from "dinero.js";
import { USD } from "@dinero.js/currencies"
import money from "money-math";

export const useMoneyMath = (currency = USD) => {
    const toMoney = (preAmount) => {
        const amounts = preAmount.toString().split('.')
        let amount = amounts[0]
        let scale = 0
        if (amounts.length > 1) {
            amount+= amounts[1]
            scale = amounts[1].length
        }
        return dinero({ amount: Number(amount), currency, scale })
    }

    const add = (amount1, amount2) => {
        try {
            let result = money.add(amount1.toString(), amount2.toString())
            return result
        } catch (e) {
            return amount1 + amount2
        }
    }

    const isBiggerThan = ()=> {
        money
    }

    return {
        add
    }
}
