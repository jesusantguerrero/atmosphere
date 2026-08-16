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
            mobileOnly: true,
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
            // Inbox is the AI triage surface — an integrating-layer destination
            // (like Calendar) where captured items land to be classified. Always
            // shown; not gated by a module.
            icon: 'fas fa-inbox',
            name: 'inbox',
            label: t('Inbox'),
            to: '/inbox',
            as: Link,
            isActiveFunction(url: string, currentPath: string) {
                return /^\/inbox/.test(currentPath)
            }
        },
        {
            separator: true
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
            icon: 'fas fa-house-user',
            label: t('Household'),
            to: '/housing',
            as: Link,
            // Profiles/Family now live INSIDE Household as the "People" sub-tab.
            // Keep the pillar visible if either module is on, and highlight it
            // when viewing /loger-profiles so the context stays coherent.
            hidden: !(isModuleEnabled('housing') || isModuleEnabled('profiles')),
            isActiveFunction(url:string, currentPath: string) {
                return /housing|loger-profiles/.test(currentPath)
             }
        }
    ];

    // Desktop sidebar: visible items minus mobile-only entries (Shopping lives
    // in the right widget panel on desktop, see FinanceWidget).
    const desktopMenu = appMenu.filter(item => !item.hidden && !item.mobileOnly);

    // Mobile bottom-nav (v3 navbar): FIVE FIXED pillars. The bar always shows
    // the same 5 hubs regardless of module enablement, so it stays predictable
    // across personas (per the agreed nav design). The "+" capture button is
    // NOT a nav slot anymore — it floats above the bar, offset right
    // (Maple-style), and lives in MobileQuickCapture.vue.
    // Voz cálida en los labels: Hoy · Agenda · Comida · Tareas · Dinero.
    //   Hoy    = home/panel cockpit (/today)
    //   Agenda = calendar + rutinas (/calendar)
    //   Comida = meal plan + shopping (/meals/overview)
    //   Tareas = the Hogar pillar: chores + reminders (/housing)
    //   Dinero = finance pillar (/finance)
    const mobileMenu = [
        { icon: 'fas fa-bolt',         name: 'Today',  label: t('Today'),  to: '/today',          as: Link },
        { icon: 'fas fa-calendar',     name: 'Agenda', label: t('Agenda'), to: '/calendar',       as: Link },
        { icon: 'fas fa-utensils',     name: 'Food',   label: t('Food'),   to: '/meals/overview', as: Link },
        { icon: 'fas fa-check-circle', name: 'Tasks',  label: t('Tasks'),  to: '/housing',        as: Link },
        { icon: 'fas fa-dollar-sign',  name: 'Money',  label: t('Money'),  to: '/finance',        as: Link },
    ];

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
            // Lands on the Settings hub (grouped cards) instead of
            // jumping straight to the Jetstream profile page. Users
            // who want Profile still find it in the first card.
            to: '/settings',
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
        date_format: '',
        cash_withdrawal_account_id: ''
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
