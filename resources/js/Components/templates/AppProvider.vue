<script setup lang="ts">
    import { ref, computed } from "vue";
    import { useCssVar } from '@vueuse/core';
    import { GlobalThemeOverrides, NConfigProvider, darkTheme } from 'naive-ui'
    import { theme } from "../../../../tailwindTheme/index.js";
    import { useDarkMode } from '@/composables/useDarkMode';

    const { isDark } = useDarkMode();
    const provider = ref()
    const bgColor = useCssVar('--colors-primary', provider, { initialValue: "#f20"} )

    const { primary } = theme.defaultLight;

    const themeOverrides = computed<GlobalThemeOverrides>(() => {
        return {
            Select: {
                common: {
                    primaryColor: primary,
                    actionColor: primary,

                },
                peers: {
                    InternalSelection: {

                    },
                    InternalSelectMenu: {
                    }
                }
            }
            // ...
        }
    })


</script>

<template>
    <NConfigProvider tag="div" ref="provider" :theme="isDark ? darkTheme : null" :theme-overrides="themeOverrides">
        <slot />
    </NConfigProvider>
</template>



