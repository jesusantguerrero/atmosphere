<script setup lang="ts">
    import { ref, computed } from "vue";
    import { useCssVar } from '@vueuse/core';
    import { GlobalThemeOverrides, NConfigProvider, darkTheme, enUS, esAR, dateEnUS, dateEsAR } from 'naive-ui'
    import { useI18n } from 'vue-i18n'
    import { theme } from "../../../../tailwindTheme/index.js";
    import { useDarkMode } from '@/composables/useDarkMode';

    const { isDark } = useDarkMode();
    // Localize naive-ui widgets (datepicker weekdays/months, etc.) with the app locale.
    const { locale } = useI18n();
    const isEs = computed(() => String(locale.value || '').toLowerCase().startsWith('es'));
    const nLocale = computed(() => (isEs.value ? esAR : enUS));
    const nDateLocale = computed(() => (isEs.value ? dateEsAR : dateEnUS));
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
    <NConfigProvider tag="div" ref="provider" :theme="isDark ? darkTheme : null" :theme-overrides="themeOverrides" :locale="nLocale" :date-locale="nDateLocale">
        <slot />
    </NConfigProvider>
</template>



