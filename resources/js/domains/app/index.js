import { Link } from "@inertiajs/inertia-vue3"

export const appMenu =  [
    {
        icon: 'home',
        name: 'home',
        label: 'Home',
        to: '/dashboard',
        as: Link
    },
    {
        icon: 'far fa-calendar-alt',
        label: 'Meal Planer',
        name: 'mealPlanner',
        to: '/meal-planner',
        as: Link
    },
    {
        icon: 'fas fa-dollar-sign',
        label: 'Finance',
        name: 'finance',
        to: '/finance',
        as: Link
    },
    {
        icon: 'fas fa-heart',
        label: 'Relationship',
        to: '/relationship',
        as: Link
    },
    {
        icon: 'fas fa-home',
        label: 'Home Projects',
        to: '/projects',
        as: Link
    }
]
