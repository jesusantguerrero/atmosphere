import colors from 'tailwindcss/colors'

export const makeOptions = (options, [id, label] = ['id', 'label']) => {
    try {
        if (typeof options == 'object') {
            return Object.entries(options).map(([key, value]) => ({
                value: value[id] || key,
                label: value[label] || value
            }));
        }
        return options && options.map(option => {
            return {
                label: option[label] || option,
                value: option[id] || option,
            }
        })
    } catch (e) {
        return []
    }
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
