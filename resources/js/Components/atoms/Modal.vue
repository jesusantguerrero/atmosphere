<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 overflow-y-auto custom-modal sm:px-0" scroll-region :class="containerClass">
                <transition
                    enter-active-class="duration-300 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-show="show" class="fixed inset-0 transition-all transform" @click="close">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                </transition>

                <transition
                    enter-active-class="duration-300 ease-out"
                    enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                    enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-active-class="duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                    class="md:mb-6  overflow-hidden transition-all transform fixed bottom-0 md:relative bg-base-lvl-3 rounded-lg shadow-xl sm:w-full sm:mx-auto"
                    :class="[maxWidthClass, fullHeight && 'h-screen flex flex-col']">
                        <slot v-if="show" :close="close" />
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from "vue";

const emit = defineEmits(['close'])

const props = defineProps({
    show: {
        default: false
    },
    maxWidth: {
        default: '2xl'
    },
    closeable: {
        default: true
    },
    isOpen: {
        type: Boolean
    },
    automatic: {
        type: Boolean
    },
    fullHeight: {
        type: Boolean,
    }
});

watch(() => props.show, (show) => {
  if (show) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = null
    }
}, {
    immediate: true,
})

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close()
    }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))

const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        'mobile': 'w-full',
    }[props.maxWidth || '2xl']
})

const containerClass = computed(() => {
    return {
        'sm': 'px-4 py-6',
        'md': 'px-4 py-6',
        'lg': 'px-4 py-6',
        'xl': 'px-4 py-6',
        '2xl': 'px-4 py-6',
        'mobile': 'w-full',
    }[props.maxWidth || '2xl']
})


const classes = computed(() => {

})
</script>

<style>
.custom-modal {
    z-index: 1002;
}
</style>
