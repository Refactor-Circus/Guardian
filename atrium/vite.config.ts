import ui from '@nuxt/ui/vite'
import { atriumAutoImports, atriumComponentResolver, atriumModule, atriumUiConfig } from '@refactor-circus/atrium/vite'

// Guardian's Atrium module: `pnpm build` writes ../dist/atrium (committed), `pnpm test` runs Vitest.
const config = atriumModule({ key: 'guardian', outDir: '../dist/atrium', inputs: ['src', '../client/src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml'], test: { setupFiles: ['test/setup.ts'] } })

// Stopgap until the SDK's nuxtUi() allows for it: installed from git, the SDK's
// sources sit under node_modules, which Nuxt UI's component resolver skips, so
// its own `<Atrium*>` and `<U*>` tags would not resolve under Vitest. This is
// the same Nuxt UI plugin, resolving components in the SDK's sources too.
if (process.env.VITEST) {
  config.plugins = [
    config.plugins?.[0],
    ui({
      ui: atriumUiConfig,
      colorMode: true,
      autoImport: { imports: atriumAutoImports({ ui: false }) as never, dts: false, vueTemplate: true, exclude: [/[\\/]node_modules[\\/]/, /[\\/]\.git[\\/]/] },
      components: {
        dirs: [],
        resolvers: [atriumComponentResolver({ ui: false, direct: true })],
        dts: false,
        // One pattern, not a list: Nuxt UI would append its own list (which skips the SDK) to a list.
        exclude: /[\\/](?:node_modules[\\/](?!\.pnpm|@nuxt[\\/]ui|@refactor-circus[\\/]atrium[\\/])|\.git[\\/])/
      }
    })
  ]
}

export default config
