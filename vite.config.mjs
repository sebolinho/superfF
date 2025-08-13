import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import viteImagemin from 'vite-plugin-imagemin';
import viteCompression from 'vite-plugin-compression';

export default defineConfig({
  build: {
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },

  plugins: [
    laravel({
      input: ['resources/scss/app.scss', 'resources/js/app.js'],
      refresh: true,
    }),

    viteImagemin({
      gifsicle: {
        optimizationLevel: 3,
        interlaced: true,
      },
      optipng: {
        optimizationLevel: 7,
      },
      mozjpeg: {
        quality: 50,
      },
      pngquant: {
        quality: [0.4, 0.6],
        speed: 1,
      },
      svgo: {
        plugins: [
          { name: 'removeViewBox' },
          { name: 'cleanupAttrs', active: true },
        ],
      },
      webp: {
        quality: 50,
      },
    }),

    viteCompression({
      algorithm: 'brotli',
      ext: '.br',
    })
  ],
});