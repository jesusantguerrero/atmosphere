import { formatDate, formatMoney } from "@/utils"
import { reactive } from "vue"

const AppSettings = reactive({
    timezone: '',
    primary_currency_code: 'USD',
    currency_symbol_option: 'before',
    date_format: ''
})

export const useAppConfig = () => {

    const formatAppDate = (value: string) => {
        return formatDate(value);
    }

    const formatAppMoney = (value: string, currencyCode = AppSettings.primary_currency_code) => {
        return formatMoney(value, currencyCode)
    }

    return {
        formatMoney: formatAppMoney,
        formatDate: formatAppDate
    }
}
