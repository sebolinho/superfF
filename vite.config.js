import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import viteImagemin from 'vite-plugin-imagemin';
import viteCompression from 'vite-plugin-compression';
import { viteStaticCopy } from 'vite-plugin-static-copy';

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
      input: ['resources/css/app.css', 'resources/js/app.js'],
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
    }),

    // Plugin para copiar arquivos estáticos que não são importados diretamente.
    // Útil para fontes, workers, ou assets de bibliotecas JS que precisam
    // estar na pasta 'public/build'.
    viteStaticCopy({
      targets: [
        {
          src: 'resources/fonts', // Copia a pasta de fontes
          dest: 'assets'          // para 'public/build/assets/fonts'
        }
        // Você pode adicionar mais alvos aqui se necessário.
      ]
    })
  ],
});