<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from "vue";

const props = defineProps({
    align: {
        default: 'right'
    },
    width: {
        default: '48'
    },
    contentClasses: {
        default: () => ['py-1', 'bg-base-lvl-3']
    }
})

const emit = defineEmits(['update:open'])

const open = ref(false)
const toggleOpen = (state: number|boolean) => {
    open.value = state == undefined ? !open.value : !!state;
    emit('update:open', open.value)
}

const closeOnEscape = (e) => {
    if (open.value && e.keyCode === 27) {
        open.value = false
    }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))


const widthClass = computed(() => {
    return {
        '48': 'w-48',
    }[props.width.toString()]
})

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'origin-top-left left-0'
    } else if (props.align === 'right') {
        return 'origin-top-right right-0'
    } else {
        return 'origin-top'
    }
})
</script>

<template>
    <div class="relative">
        <div @click="toggleOpen()">
            <slot name="trigger"></slot>
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="toggleOpen()">
        </div>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0">
            <div v-show="open"
                    class="absolute z-50 mt-2 rounded-md shadow-lg"
                    :class="[widthClass, alignmentClasses]"
                    style="display: none;"
                    @click="toggleOpen(false)">
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>


