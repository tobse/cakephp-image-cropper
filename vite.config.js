import { defineConfig } from 'vite';
import { resolve } from 'node:path';

/**
 * Build the plugin's front-end bundle into `webroot/` so CakePHP's asset
 * middleware can serve it from `/image_cropper/js/image-cropper.js` and
 * `/image_cropper/css/image-cropper.css` without any host-app build step.
 *
 * The bundle is emitted as a self-executing IIFE that has no external runtime
 * dependencies (Cropper.js is bundled in), so it can simply be dropped into a
 * page via the CropperHelper.
 */
export default defineConfig({
  build: {
    outDir: 'webroot',
    emptyOutDir: false,
    cssCodeSplit: false,
    lib: {
      entry: resolve(__dirname, 'resources/js/image-cropper.js'),
      name: 'ImageCropper',
      formats: ['iife'],
      fileName: () => 'js/image-cropper.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'css/image-cropper.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
});
