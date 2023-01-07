<template>
<button
    v-bind="$attrs"
    class="flex items-center justify-center w-16 h-16 text-3xl text-white bg-primary rounded-full shadow-md cursor-pointer"
>
    <slot />
</button>
</template>
