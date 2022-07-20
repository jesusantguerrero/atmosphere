<template>
<div class="w-full text-right">
    <span class="rounded-3xl px-5 py-1 text-white cursor-pointer" :class="badgeClass">
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
        good: 'bg-green-600',
        danger: 'bg-red-600',
        needs: 'bg-yellow-600',
        overspend: 'bg-yellow-600',
        default: 'bg-base-600'
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
