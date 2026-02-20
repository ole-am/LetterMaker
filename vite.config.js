import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: '.',
        emptyDir: false,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'src/main.js'),
            },
            output: {
                entryFileNames: 'js/lettermaker-[name].js',
                assetFileNames: 'css/lettermaker-[name].[ext]',
            },
        },
    },
})
