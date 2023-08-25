<script lang="ts">
    export default {
        props: ['on'],
    }
</script>

<template>
    <div>
        <transition leave-active-class="transition duration-1000 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-show="on" class="text-sm text-body">
                <slot />
            </div>
        </transition>
    </div>
</template>
