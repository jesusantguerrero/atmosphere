export const makeOptions = (options, [value, label]) => {
    return options.map(option => {
        return {
            label: option[label] || option,
            value: option[value] || option,
        }
    })
}
