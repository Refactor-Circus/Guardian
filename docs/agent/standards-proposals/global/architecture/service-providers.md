---
key: architecture/service-providers
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/standards-feedback.md row architecture/service-providers (booting() callback)"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/decisions.md D-023, D-032, D-038"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Review round 1 fixes: 'architecture/service-providers (minor) ... booting() callback'"
  - "/Users/jay/Projects/packages/Keystone/src/Support/PackageServiceProvider.php:113-122 registerAtriumPlugin() returns early unless configKey('ui.enabled') === true"
  - "post-mortem root cause spec_gap: 'the guardian.ui.enabled key (D-038)'"
---

## Rationale

This job hit two gaps. First, Guardian must stay inert without Roster (D-023, D-032), but the standard says to register DomainServiceProvider in register() and also never to read host state there. Guardian solved this with a booting() callback, which a review flagged as a minor finding and which needed a standards-feedback row. Second, Keystone's registerAtriumPlugin() silently does nothing unless {package}.ui.enabled is true, and no standard says so. guardian.ui.enabled was found only during the build (D-038), after group 5's code depended on it. The post-mortem lists this as a spec gap.

## Proposed text

# Service Providers

`register()` binds and merges config. `boot()` loads resources and registers surfaces: console-only wiring goes behind `runningInConsole()`, and optional surfaces go behind a strict config flag.

## register()

- Container bindings and `mergeConfigFrom()` go here, so the host app's published config overrides the package defaults.
- Register `Domains\DomainServiceProvider` here; see [[domain-modules]].
- Never read host app state (resolved services, request, auth) during registration, because other providers may not have registered yet.

## A package that is inert without a peer

Some packages register their domains only when a peer package is present (for example, an add-on that needs the package it extends). Asking the gate inside `register()` would resolve it before a host or test can rebind it. Instead:

- Register `DomainServiceProvider` from a `$this->app->booting()` callback that asks one integration gate (`Support\Integration`). `booting()` runs before any provider boots, so the domain providers still boot with the rest.
- When the gate is closed, register nothing but the `about` line that says why (`Inactive: install {vendor}/{peer}`).

```php
public function register(): void
{
    $this->registerPackage();

    $this->app->booting(function (): void {
        if ($this->app->make(Integration::class)->peerInstalled()) {
            $this->app->register(DomainServiceProvider::class);
        }
    });
}
```

## boot()

- Load resources with Laravel's native APIs: `loadRoutesFrom`, `loadViewsFrom`, `loadTranslationsFrom`, `loadMigrationsFrom`.
- Read settings with `config()` after the merge. Never call `env()` outside config files.
- Keep wiring in `register()`/`boot()` as explicit Laravel calls. Extract a private method only for a real repeated concern or for one optional surface. Don't swap in `spatie/laravel-package-tools` by default.

## Console-only wiring

- `publishes()`, `publishesMigrations()` and `commands()` sit behind `$this->app->runningInConsole()`. Web-only concerns are never registered unconditionally.
- Every publishable group carries the umbrella tag plus its own: `{package}` plus `{package}-config`, `{package}-views`, `{package}-lang` or `{package}-migrations`, so consumers can publish one group or all of them.
- Migrations publish with `publishesMigrations()`, which re-dates the files.

```php
if ($this->app->runningInConsole()) {
    $this->publishes([
        __DIR__.'/../config/{package}.php' => config_path('{package}.php'),
    ], ['{package}', '{package}-config']);

    $this->commands([InstallCommand::class]);
}
```

## Optional surfaces

- A surface consumers can switch off (UI, API routes, a transport) registers from `boot()` in its own private `register{Surface}()` method and is driven entirely by config.
- Gate it with `config('{package}.{surface}.enabled') === true`. Comparing against literal `true` fails closed, so `'1'`, `'yes'` or a typo never exposes an endpoint.
- Every surface the provider gates has its `{surface}.enabled` key in the shipped config file, so the flag exists before the surface is written.
- Path, domain, middleware and gate come from config. Nothing is hardcoded in the provider.

## Tests

Cover the observable wiring: merged config, loaded routes, publish tags, registered commands, and each optional surface both on and off. A package that is inert without a peer also tests the inert boot.

## Reference implementation (Refactor Circus)

`{Package}ServiceProvider` extends `RefactorCircus\Keystone\Support\PackageServiceProvider`. It returns the package from `definition()`, calls `registerPackage()`, and uses the base helpers (`registerPolicies()`, `registerMcpServer()`, `registerAtriumPlugin()`, `loadHistoryRoutes()`). `registerAtriumPlugin()` does nothing unless Atrium is installed and `{package}.ui.enabled` is `true`, so any package with an Atrium plugin ships `'ui' => ['enabled' => true]` in its config. Domain providers extend `Keystone\Support\ServiceProvider`, whose `loadApiRoutesFrom()` skips routes unless `{package}.routes.enabled` is `true`.

