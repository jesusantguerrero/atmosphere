import './bootstrap';
import '../css/app.css';
import "atmosphere-ui/style.css"
import "vue-multiselect/dist/vue-multiselect.css"
// Import modules...
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import VueMultiselect from 'vue-multiselect'
import { autoAnimatePlugin } from '@formkit/auto-animate/vue'
import { createPinia } from 'pinia';

const localesMessages = Object.fromEntries(
    Object.entries(
      import.meta.glob('../lang/*.json', { eager: true }))
      .map(([key, value]) => {
        const yaml = key.endsWith('.json')
        return [key.slice(8, yaml ? -5 : -4), value.default]
      }),
)


const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, app, props, plugin }) {
        const i18n = createI18n({
            locale: props.initialPage.props.locale,
            fallbackLocale: 'en',
            messages: localesMessages,
            legacy: false,
        })

        const t = (...param) => i18n.global.t(...param)
        window.t = t

        createApp({ render: () => h(app, props)})
        .use(plugin)
        .use(i18n)
        .use(pinia)
        .use(ZiggyVue, Ziggy)
        .use(autoAnimatePlugin)
        .component('Multiselect', VueMultiselect)
        .mixin({
            methods: {
                t
            },
            data() {
                return {
                    panelShadow: 'shadow-none',
                    cardShadow: 'shadow-none',
                }
            }
        })
        .mount(el);
    }
});

InertiaProgress.init({ color: '#4B5563' });
