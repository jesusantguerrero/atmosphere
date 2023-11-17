import IconTransferVue from "@/Components/icons/IconTransfer.vue";
import { Link } from "@inertiajs/vue3"
import { cloneDeep } from "lodash";
export * from "./menus";

export const useAppMenu = t => {
    const appMenu =  [
        {
            icon: 'fa fa-home',
            name: 'Dashboard',
            label: t('Dashboard'),
            to: '/dashboard',
            as: Link
        },
        {
            icon: 'far fa-calendar-alt',
            label: t('Meals'),
            name: 'mealPlanner',
            to: '/meals/overview',
            as: Link,
            isActiveFunction(url, currentPath) {
                return /meal-planner|meals|ingredients/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-dollar-sign',
            label: t('Finance'),
            name: 'finance',
            to: '/finance',
            as: Link,
            isActiveFunction(url, currentPath) {
               return /finance|budgets/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-home',
            label: t('Housing'),
            to: '/housing',
            as: Link,
            isActiveFunction(url, currentPath) {
                return /housing/.test(currentPath)
             }
        },
        {
            icon: 'fas fa-heart',
            label: t('Relationship'),
            to: '/relationships/partner',
            as: Link
        },
        {
            icon: 'fas fa-users',
            label: t('Profiles'),
            to: '/loger-profiles',
            as: Link,
            isActiveFunction(url, currentPath) {
                return /loger-profiles/.test(currentPath)
             }
        },
        {
            icon: 'fas fa-chart-bar',
            label: t('Trends'),
            to: '/trends',
            as: Link,
            isActiveFunction(url, currentPath) {
                return /trends/.test(currentPath)
            }
        }
    ].filter(item => !item.hidden);

    let mobileMenu = cloneDeep(appMenu).splice(0, 4)
    mobileMenu.splice(2, null, {
        name: 'add',
        label: 'Add',
        icon: IconTransferVue,
        action: 'openTransactionModal'
    });

    const headerMenu =  [
        {
            icon: 'fas fa-info',
            label: t('About'),
            to: '/settings/about',
            as: Link
        },
        {
            icon: 'fas fa-question',
            label: t('Help Center'),
            to: '/settings/help',
            as: Link
        },
        {
            icon: 'fas fa-cogs',
            label: t('Settings'),
            name: 'settings',
            to: '/user/profile',
            as: Link
        },
    ];

    return {
        appMenu,
        mobileMenu,
        headerMenu
    }
}

export const DEFAULT_TIMEZONE = "UTC";

export const defaultDateFormats = ['dd MMM, yyyy', 'dd.MM.yyyy', 'MM/dd/yyyy', 'yyyy.MM.dd']

export const mapTeamFormServer = (team, prefix="team_") => {
    const regPrefix = new RegExp(prefix);
    return team.settings.reduce((acc, setting) => {
        const fieldName = setting.name.replace(regPrefix, '')
        acc[fieldName] = setting.value;
        return acc;
    }, {
        name: team.name,
        timezone:'',
        primary_currency_code: 'USD',
        currency_symbol_option: 'before',
        date_format: ''
    })
}

export const parseTeamForm = (team, prefix="team_") => {
    return Object.keys(team).reduce((acc, fieldName) => {
        acc[prefix+fieldName] = team[fieldName];
        return acc;
    }, {
        name: team.name,
    })
}
