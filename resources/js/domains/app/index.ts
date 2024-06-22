import IconTransferVue from "@/Components/icons/IconTransfer.vue";
import { Link } from "@inertiajs/vue3"
import { cloneDeep } from "lodash";
export * from "./menus";

export const moduleEnabled = (modules: any[], moduleName: string) =>  modules.find(module => module.name.toLowerCase() == moduleName && module.enabled)
export const useAppMenu = (t: any, modules: any[]) => {

    const isModuleEnabled = moduleEnabled.bind(null, modules);

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
            hidden: !isModuleEnabled('meals'),
            isActiveFunction(url: string, currentPath: string) {
                return /meal-planner|meals|ingredients/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-dollar-sign',
            label: t('Finance'),
            name: 'finance',
            to: '/finance',
            as: Link,
            hidden: !isModuleEnabled('finance'),
            isActiveFunction(url: string, currentPath: string) {
               return /finance|budgets/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-home',
            label: t('Housing'),
            to: '/housing',
            as: Link,
            hidden: !isModuleEnabled('housing'),
            isActiveFunction(url:string, currentPath: string) {
                return /housing/.test(currentPath)
             }
        },
        {
            icon: 'fas fa-users',
            label: t('Profiles'),
            to: '/loger-profiles',
            as: Link,
            hidden: !isModuleEnabled('profiles'),
            isActiveFunction(url: string, currentPath: string) {
                return /loger-profiles/.test(currentPath)
             }
        },
        {
            icon: 'fas fa-chart-bar',
            label: t('Trends'),
            to: '/trends',
            as: Link,
            isActiveFunction(url: string, currentPath: string) {
                return /trends/.test(currentPath)
            }
        }
    ].filter(item => !item.hidden);

    let mobileMenu = cloneDeep(appMenu).splice(0, 4)
    mobileMenu.splice(2, 0, {
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

export const useModuleEnabled = (modules: any[]) => ({
    isModuleEnabled: moduleEnabled.bind(null, modules),
})

export const DEFAULT_TIMEZONE = "UTC";

export const defaultDateFormats = ['dd MMM, yyyy', 'dd.MM.yyyy', 'MM/dd/yyyy', 'yyyy.MM.dd']

export const mapTeamFormServer = (team: Record<string, any>, prefix="team_") => {
    const regPrefix = new RegExp(prefix);
    return team.settings.reduce((acc: Record<string, any>, setting: Record<string, any>) => {
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

export const parseTeamForm = (team: Record<string, any>, prefix="team_") => {
    return Object.keys(team).reduce((acc: Record<string, any>, fieldName) => {
        acc[prefix+fieldName] = team[fieldName];
        return acc;
    }, {
        name: team.name,
    })
}
