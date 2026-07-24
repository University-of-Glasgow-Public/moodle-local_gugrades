import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import viteCompression from 'vite-plugin-compression';
import { webUpdateNotice } from '@plugin-web-update-notification/vite';

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    tailwindcss(),
    //viteCompression({
    //  algorithm: 'gzip',
    //  ext: '.gz',
    //  deleteOriginFile: false // Crucial: Keeps your standard app.js for development
    //})
    webUpdateNotice({
      checkInterval: 60 * 1000 * 5, // Check every 5 minutes
      hiddenDismissButton: true,
      
      // Customize the text directly in the config
      notificationProps: {
        title: 'System Update',
        description: 'A new version of MyGrades is available.',
        buttonText: 'Refresh Now',
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
    dedupe: ['vue']
  },
  build: {
    minify: false,
    sourcemap: true,
    cssMinify: 'esbuild',
    commonjsOptions: {
      include: [/vue3-easy-data-table/, /node_modules/]
    },
    chunkSizeWarningLimit: 2000,
    rollupOptions: {
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`
      },
      onwarn(warning, warn) {
        // Silence the invalid pure annotation warning specifically for node_modules
        if (warning.code === 'INVALID_ANNOTATION' && warning.message.includes('__PURE__')) {
          return;
        }
        warn(warning);
      }
    }
  },
  optimizeDeps: {
    exclude: ['vue3-easy-data-table'],
    include: ['daisyui'],
  },
  base: './'
})
