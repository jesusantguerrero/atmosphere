import { DOP } from "@dinero.js/currencies"
import { add, dinero, toFormat, toUnit } from "dinero.js"

export function useMoney() {
    const formatter = ({amount, currency }) => `${currency.code} ${getMoneyString(amount.toFixed(currency.exponent))}`

    const decimalToMoney = (decimalValue) => {
        return Number(decimalValue.replace('.', ''))
    }

    const toDecimalString = (moneyObject) => {
        return toFormat(moneyObject, ({amount }) => amount.toFixed(DOP.exponent))
    }

    const sumMoney = (array) => {
        const sum = array.reduce((total, decimalString) => {
            return add(total, toMoney(decimalString))
        }, toMoney('0.00'))
        return toDecimalString(sum)
    }

    const toMoney = (amount) => {
        let value = decimalToMoney(amount)
        return dinero({ amount: value, currency: DOP })
    }

    const formatMoney = (amount) => {
        let value = decimalToMoney(amount)
        value = dinero({ amount: value, currency: DOP })
        return toFormat(value, formatter)
    }

    const getMoneyString = (amount) => {
        return new Intl.NumberFormat("en-IN", {
            style: "currency",
            currency: "DOP",
            currencyDisplay: "symbol"
          }).format(amount || 0);
    }

    return {
        formatMoney,
        sumMoney
    }
}
