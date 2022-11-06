import { Link } from "@inertiajs/inertia-vue3"
export * from "./menus";

export const useAppMenu = t => {
    const appMenu =  [
        {
            icon: 'fa fa-home',
            name: 'home',
            label: t('Home'),
            to: '/dashboard',
            as: Link
        },
        {
            icon: 'far fa-calendar-alt',
            label: t('Meal Planner'),
            name: 'mealPlanner',
            to: '/meal-planner',
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
               return /finance|budgets|trends/.test(currentPath)
            }
        },
        {
            icon: 'fas fa-heart',
            label: t('Relationship'),
            to: '/relationships',
            as: Link
        },
        {
            icon: 'fas fa-home',
            label: t('Home Projects'),
            to: '/housing',
            as: Link
        }
    ].filter(item => !item.hidden);

    const headerMenu =  [
        {
            icon: 'fa fa-heart',
            name: 'support',
            label: t('Support project'),
            to: '/settings/support',
            as: Link
        },
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
            to: '/settings',
            as: Link
        },
    ];

    return {
        appMenu,
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
