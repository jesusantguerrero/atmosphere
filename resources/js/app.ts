import './bootstrap';
import '../css/app.css';
import "atmosphere-ui/style.css"
import "vue-multiselect/dist/vue-multiselect.css"
// Import modules...
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { createInertiaApp, router } from '@inertiajs/vue3';;
import { ZiggyVue } from './ziggy.mjs';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import VueMultiselect from 'vue-multiselect'
import { autoAnimatePlugin } from '@formkit/auto-animate/vue'
import { createPinia } from 'pinia';
import { vRipple } from './utils/vRipple';
import ElementPlus from 'element-plus'
import { ElNotification } from 'element-plus'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'

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
    title: (title) => `${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        const i18n = createI18n({
            locale: props.initialPage.props.locale,
            fallbackLocale: 'en',
            messages: localesMessages,
            legacy: false,
        })

        window.logerLocale = props.initialPage.props.locale;

        // Keep vue-i18n's locale in sync with the server locale on every Inertia
        // navigation. The i18n instance is created once with the initial locale,
        // so without this a language change only applied after a manual reload.
        router.on('navigate', (event: any) => {
            const newLocale = event?.detail?.page?.props?.locale;
            if (newLocale && i18n.global.locale.value !== newLocale) {
                i18n.global.locale.value = newLocale;
                window.logerLocale = newLocale;
            }
        });

        window.logerAppSettings = {
            currency_code: props.initialPage.props.settings?.team_primary_currency_code ?? 'USD',
            date_format: props.initialPage.props.settings?.team_date_format,
        }

        const t = (...param) => i18n.global.t(...param)
        window.t = t

        // Intercept non-Inertia responses (nginx 502/503/504, 419 session
        // expiry, HTML 500) so the user gets a friendly toast instead of
        // Inertia's raw full-page error modal (the white "502 Bad Gateway"
        // sheet). In dev we keep the modal for genuine app errors (500) so the
        // Laravel stack trace stays visible; gateway/session errors always
        // become a toast.
        const notifyServerError = (message: string) => ElNotification({
            title: t('Connection problem'),
            message,
            type: 'error',
            duration: 5000,
            position: 'bottom-right',
        });
        router.on('invalid', (event: any) => {
            const status = event?.detail?.response?.status ?? 0;
            const isGateway = [0, 419, 502, 503, 504].includes(status);
            if (import.meta.env.DEV && !isGateway) return; // keep debug modal for 500s in dev
            event.preventDefault();
            notifyServerError(status === 419
                ? t('Your session expired. Please refresh the page.')
                : t('The server did not respond. Please try again in a moment.'));
        });
        router.on('exception', (event: any) => {
            // Request never completed (network dropped / timeout).
            event.preventDefault();
            notifyServerError(t('The server did not respond. Please try again in a moment.'));
        });

        createApp({
            progress: {
              color: '#29d',
            },
            render: () => h(App, props)})
        .use(plugin)
        .use(i18n)
        .use(pinia)
        .use(ZiggyVue, Ziggy)
        .use(autoAnimatePlugin)
        .use(ElementPlus)
        .component('Multiselect', VueMultiselect)
        .directive('ripple', vRipple)
        .provide("router", router)
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
