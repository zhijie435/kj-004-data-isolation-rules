import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path
      }
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `
          $text-primary: #303133;
          $text-regular: #606266;
          $text-secondary: #909399;
          $text-placeholder: #c0c4cc;
          $color-primary: #409eff;
          $color-success: #67c23a;
          $color-warning: #e6a23c;
          $color-danger: #f56c6c;
          $color-info: #909399;
        `
      }
    }
  }
})
