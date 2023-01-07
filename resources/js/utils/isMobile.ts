import { useBreakpoints, breakpointsTailwind } from "@vueuse/core"
const { isSmaller } = useBreakpoints(breakpointsTailwind)

export const getIsMobile = () => {
    return isSmaller('md')
}
