<template>
<div class="w-full text-right">
    <span class="px-5 py-1 cursor-pointer rounded-3xl text-body-1" :class="badgeClass">
        {{ formatter(value) }}
    </span>
</div>
</template>

<script setup>
    import { computed } from "vue"
    const props = defineProps({
        value: {
            type: Number
        },
        formatter: {
            type: Function,
            default() {
                return (value) => {
                    return value
                }
            }
        }
    })

    const theme = {
        good: 'bg-success',
        danger: 'bg-danger',
        needs: 'bg-warning',
        overspend: 'bg-warning',
        default: 'bg-base-lvl-3'
    }

    const badgeClass = computed(() => {
        let themeColor = theme.default
        console.log(props.value)
        if (props.value > 0) {
            themeColor = theme.good
        } else if (props.value < 0) {
            themeColor = theme.overspend
        }
        return [themeColor]
    })
</script>
