import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import path from 'path';

export default defineConfig({
    plugins: [
        svelte()
    ],
    build: {
        lib: {
            entry: path.resolve(__dirname, 'src/main.js'),
            name: 'TestScriptSvelte',
            fileName: 'test-script-svelte',
            formats: ['es']
        },
        outDir: 'dist',
        emptyDirBeforeWrite: true,
        rollupOptions: {
            output: {
                entryFileNames: 'test-script-svelte.js',
                assetFileNames: 'test-script-svelte.[ext]'
            }
        }
    }
});
