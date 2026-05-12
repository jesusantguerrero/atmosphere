<template>
<button
    v-bind="$attrs"
    class="flex items-center justify-center text-2xl text-white rounded-full shadow-lg cursor-pointer w-14 h-14 bg-primary ring-4 ring-gray-900 active:scale-95 transition-transform"
>
    <slot />
</button>
</template>
