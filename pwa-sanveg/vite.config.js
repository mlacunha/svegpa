import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

// https://vite.dev/config/
export default defineConfig({
  base: './',
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['sanveg-icon.svg', 'sanveg-touch-icon.png', 'sanveg-icon-192.png', 'sanveg-icon-512.png'],
      manifest: {
        name: 'Sanveg PA - Defesa Agropecuária',
        short_name: 'Sanveg PA',
        description: 'PWA de Coleta Fitossanitária Offline-First',
        theme_color: '#2563eb',
        background_color: '#f8fafc',
        display: 'standalone',
        orientation: 'portrait',
        icons: [
          {
            src: '/sanveg-icon-192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/sanveg-icon-512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: '/sanveg-icon-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,json,svg,png,ico,woff,woff2,ttf,eot}'],
        maximumFileSizeToCacheInBytes: 5000000,
        // Garante que o SW novo tome controle imediatamente sem precisar fechar o app
        skipWaiting: true,
        clientsClaim: true,
      }
    })
  ],
})
