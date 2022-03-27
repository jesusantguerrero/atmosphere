export const makeOptions = (options) => {
    return options.map(option => {
        return {
            label: option,
            value: option,
        }
    })
}
