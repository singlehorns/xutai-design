import { defineConfig } from "astro/config";

export default defineConfig({
  site: "https://singlehorns.github.io",
  base: "/xutai-design",
  output: "static",
  devToolbar: {
    enabled: false
  },
  vite: {
    cacheDir: ".astro-vite"
  }
});
