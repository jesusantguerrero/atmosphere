export const PANEL_SIZES = {
    small: 'md:w-3/12',
    large: 'md:w-5/12'
}

export const priorityColors = {

}

export const matrixColors = {
    red: 'bg-red-500 text-white',
    blue: 'bg-blue-500 text-white',
    green: 'bg-green-500 text-white',
    yellow: 'bg-yellow-500 text-white'
}

const demo = import.meta.env.VITE_APP_DEMO;
export const isDemo = Boolean(demo) && demo !== 'false';
