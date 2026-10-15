import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
// TinyTank's defaults (entry points, the 'datastar' and 'tinytank' aliases, build output, manifest,
// DDEV dev server, page reloads): see the file for details.
import tinytank from './vendor/tinytank/framework/resources/vite/tinytank.js';

export default defineConfig({
    plugins: [
        tailwindcss(),
        // Entry points: resources/js/app.js plus every resources/js/pages/*.js, one bundle per page or
        // feature, so heavy JS only loads where it's used. Pass `input: [...]` to list them yourself.
        tinytank(),
    ],
});
