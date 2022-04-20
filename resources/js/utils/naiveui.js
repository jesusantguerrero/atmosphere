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
