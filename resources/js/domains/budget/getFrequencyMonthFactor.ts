import { FREQUENCY_TYPE } from "@/utils";
import { format, parseISO } from "date-fns";
import { useDatePager } from "vueuse-temporals";

export const getFrequencyMonthFactor = (recurrence, dateString) => {
    try {
        const date = typeof dateString == 'string' ? parseISO(dateString): dateString;
        const { selectedSpan } =  useDatePager({nextMode: 'month', initialDate: date })
        return recurrence.frequency == FREQUENCY_TYPE.WEEKLY
        ? selectedSpan.value.filter(
                day => {
                    return format(day, 'iiiiii').toLowerCase() == recurrence.frequency_week_day?.toLowerCase()
                }).length
        : 1;
    } catch (err) {
        return 1
    }
}
