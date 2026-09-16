import { createInertiaApp } from '@inertiajs/vue3';
import { type DefineComponent, createApp, createSSRApp, h } from 'vue';
import { translate } from './composables/useTranslation';
import { configureEcho } from '@laravel/echo-vue';
import './echo';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'SathiSaaS';

const corePages = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue');

const modulePages = import.meta.glob<{ default: DefineComponent }>([
    '../../Modules/*/resources/js/Pages/**/*.vue',
    '../../Modules/*/resources/js/pages/**/*.vue',
]);

const pages: Record<string, () => Promise<{ default: DefineComponent }>> = {
    ...corePages,
};

for (const [path, resolver] of Object.entries(modulePages)) {
    const match = path.match(
        /^\.\.\/\.\.\/Modules\/([^/]+)\/resources\/js\/[pP]ages\/(.+)\.vue$/,
    );

    if (!match) {
        continue;
    }

    const [, moduleName, pageName] = match;

    pages[`${moduleName}/${pageName}`] = resolver;
    pages[pageName] = resolver;
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: async (name) => {
        const page = pages[`./pages/${name}.vue`] ?? pages[name];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        const module = await page();

        return module.default;
    },

    setup({ el, App, props, plugin }) {
        const routeHelper = (name?: string, params?: any) => {
            if ((window as any).route) {
                return (window as any).route(name, params);
            }
            if (!name) {
                return '#';
            }
            const normalized = name.startsWith('/') ? name : `/${name.replace(/\./g, '/')}`;
            return params !== undefined ? `${normalized}/${params}` : normalized;
        };

        if (el) {
            const vueApp = createApp({
                render: () => h(App, props),
            });
            vueApp.config.globalProperties.route = routeHelper;
            vueApp.config.globalProperties.$t = translate;
            vueApp.use(plugin).mount(el);
            return;
        }

        const ssrApp = createSSRApp({
            render: () => h(App, props),
        });
        ssrApp.config.globalProperties.route = routeHelper;
        ssrApp.config.globalProperties.$t = translate;
        return ssrApp.use(plugin);
    },

    progress: {
        color: '#4B5563',
    },
});