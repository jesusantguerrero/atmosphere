import colors from 'tailwindcss/colors'

export const makeOptions = (options, ...[value, label]) => {
    if (typeof options == 'object') {
        return Object.entries(options).map(([key, value]) => ({
            value: key,
            label: value
        }));
    }
    return options.map(option => {
        return {
            label: option[label] || option,
            value: option[value] || option,
        }
    })
}


export const darkThemeOverrides = {
    DataTable: {
        borderRadius: '8px',
        common: {
            baseColor: colors.slate[600],
            bodyColor: colors.slate[600],
        }
    }
}
