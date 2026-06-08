import { fileURLToPath, URL } from "node:url";
import path from "node:path";

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

const clientRoot = fileURLToPath(new URL(".", import.meta.url));

// Finn mer her https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
      xlsx: path.resolve(clientRoot, "../../UKMrapporter/client/node_modules/xlsx"),
    },
  },
  build: {
    rollupOptions: {
        output: {
            dir: './dist/assets/',
            entryFileNames: 'build.js',
            assetFileNames: 'build.css',
            chunkFileNames: "chunk.js",
            manualChunks: undefined,
        }
    }
}
});
