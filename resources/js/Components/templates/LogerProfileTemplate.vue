
<script lang="ts" setup>
    import { computed } from 'vue';
    import { PANEL_SIZES } from '@/utils/constants';
    // @ts-ignore
    import exactMathNode from 'exact-math';

    const props = defineProps({
        title: {
            type: String
        },
        categories: {
            type: Array,
            default() {
                return []
            }
        },
        accounts: {
            type: Array,
            default() {
                return []
            }
        },
        panelSize: {
            type: String,
            validator(value: string) {
                return Object.keys(PANEL_SIZES).includes(value)
            }
        },
        forceShowPanel: {
            type: Boolean,
        }
    });

    // Styling
    const panelStyles = computed(() => {
        // @ts-ignore
        const sizes = PANEL_SIZES[props.panelSize] || PANEL_SIZES.small;
        const visible = !props.forceShowPanel && 'hidden';
        return [sizes, visible];
    })

</script>


<template>
    <article class="relative flex flex-col-reverse w-full pt-16 pb-20 mx-auto md:flex-row md:space-x-2 md:max-w-screen-2xl">
        <main class="w-full md:pr-5 md:w-10/12 md:pl-8" :class="{'mx-auto': !$slots.panel}">
            <slot />
        </main>

        <aside class="relative w-full h-screen overflow-auto md:px-2 md:block " :class="panelStyles" v-if="$slots.panel">
            <section class="px-2 md:fixed aside-content md:pr-8 ic-scroller">
                <slot name="prepend-panel" />
                <slot name="panel">

                </slot>
            </section>
        </aside>
    </article>
</template>
