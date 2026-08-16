<template>
<button
    v-bind="$attrs"
    class="flex items-center justify-center text-2xl text-white rounded-full shadow-lg cursor-pointer w-14 h-14 bg-primary ring-4 ring-base-lvl-3 active:scale-95 transition-transform"
>
    <slot />
</button>
</template>
