import { defineComponent, h } from "vue";

export default defineComponent({
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
})
