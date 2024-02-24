import { format, parseISO, startOfDay } from "date-fns"
import { differenceInMonths } from "date-fns";
export * from "./formatMoney";
export * from "./isMobile";

export const recurrenceTypes = [
    {
        value: 'MONTHLY',
        label: 'Monthly'
    },
    {
        value: 'WEEKLY',
        label: 'Weekly'
    },
    {
        value: 'DATE',
        label: 'By Date'
    }
]

export const toOrdinals = (num: number) => {
    const suffixes = new Map([
        ['one', 'st'],
        ['two', 'nd'],
        ['few', 'rd'],
        ['other', 'th'],
    ])

    const rule = (new Intl.PluralRules('en-US', { type: 'ordinal'})).select(num)
    const suffix = suffixes.get(rule)
    return `${num}${suffix}`

}

export const monthDays = () => {
    const days = [];
    for (let i = 1; i <= 31; i++) {
        days.push({
            value: i,
            label: toOrdinals(i)
        });
    }
    days.push({
        value: -1,
        label: 'End of month'
    });
    return days;
}

export const WEEK_DAYS = {
    SU: 'Sunday',
    MO: 'Monday',
    TU: 'Tuesday',
    WE: 'Wednesday',
    TH: 'Thursday',
    FR: 'Friday',
    SA: 'Saturday',
}

export const FREQUENCY_TYPE = {
    WEEKLY: 'WEEKLY',
    MONTHLY: 'MONTHLY',
    BY_DATE: 'BY_DATE'
}

export const getDateFromIso = (isoDateString: string) => {
    return startOfDay(new Date(isoDateString))
}

export const generateRandomColor = () => `#${Math.floor(Math.random() * 16777215).toString(16)}`;

export enum MonthTypeFormat {
    short = 'MMM',
    long = 'MMMM',
    monthYear = 'MMMM yyyy'
}
export const formatMonth = (dateString: string | Date, type: MonthTypeFormat = MonthTypeFormat.short ) => {
    try {
        if (typeof dateString == 'string') {
            return format(parseISO(dateString), type)
        } else {
            return format(dateString, type)
        }
    } catch (err) {
        return dateString
    }
}

export const formatDate = (dateISOString: string, placeholder?: string, formatString = "MMM dd, yyyy") => {
    if (!dateISOString && placeholder) return placeholder;
    try {
        return dateISOString && format(parseISO(dateISOString + "T00:00:00"), formatString);
    }
    catch (e) {
        return dateISOString;
    }
};


export const isCurrentMonth = (dateString: string) => {
    return !differenceInMonths(new Date(), parseISO(dateString));
}
