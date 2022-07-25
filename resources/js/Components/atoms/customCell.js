import { h } from "vue";

export default {
    props: {
        col: {
            type: Object
        },
        data: {
            type: Object
        }
    },
    setup(props, { slots }) {
        return () => h('div', props.col?.render(props.data))
    }
}
