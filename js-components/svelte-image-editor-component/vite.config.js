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
            name: 'EditorSvelteComponent',
            fileName: 'editor-svelte-component',
            formats: ['es']
        },
        outDir: 'dist',
        emptyDirBeforeWrite: true,
        rollupOptions: {
            output: {
                entryFileNames: 'editor-svelte-component.js',
                assetFileNames: 'editor-svelte-component.[ext]'
            }
        }
    }
});
