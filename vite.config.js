import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';

// TODO: remove these warnings once the issue below has been resolved, e.g. bootstrap v5.5
// https://github.com/twbs/bootstrap/issues/40962
const BOOTSTRAP_DEPRECATIONS = [
    'import', 'global-builtin', 'color-functions', 'mixed-decls'
];

// Git-ignored theme override imported by `app.scss` (see `_custom.scss.example`).
// `app.scss` always imports it, so an empty stub is created when it is missing.
// An existing file is never touched.
const CUSTOM_THEME_FILE = path.resolve(__dirname, 'resources/sass/themes/_custom.scss');

function ensureCustomThemeFile() {
    if (fs.existsSync(CUSTOM_THEME_FILE)) {
        return;
    }

    fs.writeFileSync(
        CUSTOM_THEME_FILE,
        `// Drop instance theme override here to brand this installation.\n`
        + `// This file is gitignored. See themes/_custom.scss.example for the format.\n`,
    );
}

export default () => {
    // Make sure the optional theme override exists before compiling the front-end.
    ensureCustomThemeFile();

    // Return the Vite configuration
    return defineConfig({
        plugins: [
            vue(),
            laravel({
                input: [
                    "/resources/sass/app.scss",
                    "/resources/js/app.js",
                    "/resources/js/theme.js",
                ],
                refresh: true,
            }),
        ],
        css: {
            preprocessorOptions: {
                scss: {
                    // Silence Sass deprecation warnings originating from
                    // dependencies (e.g. Bootstrap's legacy @import and
                    // color functions) while keeping warnings from our own SCSS.
                    quietDeps: true,
                    silenceDeprecations: BOOTSTRAP_DEPRECATIONS
                },
            },
        },
        resolve: {
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
                '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
                '@': '/resources/js',
            },
        },
        server: {
            host: '127.0.0.1',
        }
    });
}