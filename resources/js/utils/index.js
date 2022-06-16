import { format } from "date-fns"
import { Inertia } from "@inertiajs/inertia";

export const targetTypes = [
    {
        value: 'spending',
        label: 'Spending',
        description: `For groceries, holidays, vacations
        Fund up to an amount with the ability to spend from it along the way
        `,

    }, {
        value: 'saving_balance',
        label: 'Saving Balance',
        description: `down payments, emergency fund
        Save this amount over time and maintain the balance by replenishing any money spent
        `
    }, {
        value: 'savings_monthly',
        label: 'Savings Monthly',
        description: `For saving goals with unknown targets
        Contribute this amount every month until you disable this target
        `
    }, {
        value: 'debt_monthly_payment',
        label: 'Debt Monthly Payment',
        description: `Use for: Mortgage, student loans, auto loans, etc
        Budget for payments until you are debt free
        `
    }
];

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

const toOrdinals = (num) => {
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

export const getDateFromIso = (isoDateString) => {
    return startOfDay(new Date(isoDateString))
}

export const filterParams = (mainDateField, {dates}) => {
    let filters = [];

    if (dates.startDate) {
      let dateFilterValue = format(dates.startDate, 'yyyy-MM-dd');
      if (dates.endDate) {
        dateFilterValue += `~${format(dates.endDate, 'yyyy-MM-dd')}`;
      }
      filters.push(`filter[${mainDateField}]=${dateFilterValue}`);
    }

    return filters.join("&");
  }

export const groupParams = (groupValue) => {
    return `group=${groupValue}`;
  }

export const updateSearch = (searchOptions, dateSpan) => {
      let params = [
          filterParams('date', { dates: {
              startDate: searchOptions.date.startDate,
              endDate: dateSpan ? dateSpan[dateSpan.length - 1] : null
          } }),
          groupParams(searchOptions.group)
    ]
    params = params.filter(value => value).join("&");
    Inertia.visit(`${window.location.pathname}?${params}`, {
        preserveState: true,
    })
}
