import IconTransferVue from "@/Components/icons/IconTransfer.vue";
import IconPlus from "@/Components/icons/IconPlus.vue";
import { Link } from "@inertiajs/vue3"
import { cloneDeep } from "lodash";
export * from "./menus";

export const moduleEnabled = (modules: any[], moduleName: string) =>  modules.find(module => module.name.toLowerCase() == moduleName && module.enabled)
export const useAppMenu = (t: any, modules: any[]) => {

    const isModuleEnabled = moduleEnabled.bind(null, modules);

    const appMenu =  [
        {
            icon: 'fas fa-bolt',
            name: 'Today',
            label: t('Today'),
            to: '/today',
            as: Link
        },
        {
            icon: 'fa fa-home',
            name: 'Dashboard',
            label: t('Dashboard'),
            to: '/dashboard',
            as: Link
        },
        {
            icon: 'fas fa-chart-bar',
            label: t('Trends'),
            to: '/trends',
            as: Link,
            isActiveFunction(url: string, currentPath: string) {
                return /trends/.test(currentPath)
            }
        },
        {
            // Calendar is a universal integrating-layer view (pulls dated
            // items from every other pillar). Always shown — not a pillar.
            icon: 'fas fa-calendar',
            label: t('Calendar'),
            to: '/calendar',
            as: Link,
            isActiveFunction(url: string, currentPath: string) {
                return /^\/calendar/.test(currentPath)
            }
        },
        {
            separator: true
        },
        // Pillar labels follow .planning/family-os-structure.md naming:
        // Food (was Meal Planner), Home (was Housing), Family (was Profiles).
        // Routes stay unchanged — only the visible labels move.
        {
            icon: 'fas fa-utensils',
            label: t('Food'),
            name: 'mealPlanner',
            to: '/meals/overview',
            as: Link,
            hidden: !isModuleEnabled('meals'),
            isActiveFunction(url: string, currentPath: string) {
                return /meal-planner|meals|ingredients/.test(currentPath)
            }
        },
        {
            // Shopping list is reachable from the right-side widget on desktop
            // and the mobile bottom-nav (mobileTargets below). Hidden from the
            // desktop sidebar to keep that rail focused on top-level pillars —
            // shopping isn't a pillar, it's a tool that lives inside Food.
            icon: 'fas fa-shopping-cart',
            label: t('Shopping'),
            name: 'shopping',
            to: '/shopping',
            as: Link,
            mobileOnly: true,
            isActiveFunction(url: string, currentPath: string) {
                return /^\/shopping/.test(currentPath)
            }
        },
        {
            // Finance is the canonical Loger pillar (per marketing landing
            // and README) — always shown, never gated by module enablement.
            // Fresh users without any modules enabled still see Finance in
            // both the desktop sidebar and the mobile bottom-nav.
            icon: 'fas fa-dollar-sign',
            label: t('Finance'),
            name: 'finance',
            to: '/finance',
            as: Link,
            isActiveFunction(url: string, currentPath: string) {
               return /finance|budgets/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-house-user',
            label: t('Household'),
            to: '/housing',
            as: Link,
            hidden: !isModuleEnabled('housing'),
            isActiveFunction(url:string, currentPath: string) {
                return /housing/.test(currentPath)
             }
        },
        {
            icon: 'fas fa-users',
            label: t('Family'),
            to: '/loger-profiles',
            as: Link,
            hidden: !isModuleEnabled('profiles'),
            isActiveFunction(url: string, currentPath: string) {
                return /loger-profiles/.test(currentPath)
             }
        }
    ];

    // Desktop sidebar: visible items minus mobile-only entries (Shopping lives
    // in the right widget panel on desktop, see FinanceWidget).
    const desktopMenu = appMenu.filter(item => !item.hidden && !item.mobileOnly);

    // Mobile bottom-nav: aim for 4 nav slots + 1 centered FAB = 5 items, so
    // the layout stays balanced (2 + FAB + 2) regardless of which modules
    // a user has activated. Slots fill in priority order:
    //   1. Primary pillars (the daily-use ones — Today, Food, Shopping, Finance)
    //   2. Fallback non-pillars (Calendar, Trends) when a pillar is module-
    //      gated off, so fresh users see a useful nav before activating modules.
    const primaryMobileTargets = ['/today', '/meals/overview', '/shopping', '/finance'];
    const fallbackMobileTargets = ['/calendar', '/trends'];
    const targetSlots = 4;

    let mobileMenu = cloneDeep(appMenu)
        .filter(item => !item.hidden && primaryMobileTargets.includes(item.to))
        .sort((a, b) => primaryMobileTargets.indexOf(a.to) - primaryMobileTargets.indexOf(b.to));

    if (mobileMenu.length < targetSlots) {
        const used = new Set(mobileMenu.map(i => i.to));
        const fallbacks = cloneDeep(appMenu)
            .filter(item => !item.hidden && fallbackMobileTargets.includes(item.to) && !used.has(item.to))
            .slice(0, targetSlots - mobileMenu.length);
        // Insert fallbacks AFTER Today so the priority items still bookend.
        // Today always sits at the leading edge; Finance (if present) at the trailing.
        mobileMenu.splice(1, 0, ...fallbacks);
    }

    // Insert the Add FAB at the visual midpoint so it stays centered when
    // we have an even count (2 left + FAB + 2 right). Math.floor keeps the
    // FAB centered on 4-item layouts (slots 0,1,FAB,2,3).
    const fabMidpoint = Math.floor(mobileMenu.length / 2);
    mobileMenu.splice(fabMidpoint, 0, {
        name: 'add',
        label: 'Add',
        icon: IconPlus,
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
        appMenu: desktopMenu,
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
