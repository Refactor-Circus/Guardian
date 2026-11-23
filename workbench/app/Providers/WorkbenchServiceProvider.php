<?php

namespace Workbench\App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use stdClass;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo app: Guardian on the workbench User, with
 * its JSON API mounted, the Sanctum token driver and passkeys available.
 *
 * It also holds the one Scramble configuration `workbench:openapi` exports
 * openapi.json with (`composer client:generate`). Scramble is a dev
 * dependency: nothing in src/ knows about it.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            'roster.users.model' => User::class,
            'guardian.routes.enabled' => true,
            'guardian.tokens.driver' => 'sanctum',
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // After every provider booted, so Scramble's own boot, which loads
        // config('scramble'), cannot overwrite it.
        $this->app->booted(fn () => $this->documentApi());

        if ($this->app->runningInConsole()) {
            $this->registerOpenApiCommand();
        }
    }

    /**
     * Pin the export to Guardian's JSON API and to fixed values, so the
     * committed openapi.json changes only when the API does: the routes
     * named `guardian.*` under `guardian.routes.prefix` (never the SSO
     * sign-in pages or the SCIM protocol server), each under its route name.
     */
    private function documentApi(): void
    {
        $prefix = trim((string) config('guardian.routes.prefix'), '/');

        Scramble::configure()
            ->useConfig(array_replace_recursive((array) config('scramble'), [
                'api_path' => $prefix,
                'info' => [
                    'version' => '2.0.0',
                    'description' => 'The Guardian JSON API: roles and permissions, the security log, two-factor, sessions and devices, passwords, API tokens, login protection, SSO connections and SCIM tokens. Paths are relative to the `guardian.routes.prefix` of the host application.',
                ],
                'ui' => ['title' => 'Guardian API'],
            ]))
            ->routes(fn (Route $route): bool => WorkbenchServiceProvider::isApiRoute($route, $prefix))
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $operation->setOperationId((string) $routeInfo->route->getName());
            })
            // A relative server instead of one built from APP_URL: clients
            // pass their own base URL, prefix included.
            ->withDocumentTransformers(function (OpenApi $openApi) use ($prefix): void {
                $openApi->servers = [Server::make('/'.$prefix)];
            });
    }

    /**
     * Whether a route is one of Guardian's JSON API routes.
     */
    public static function isApiRoute(Route $route, string $prefix): bool
    {
        return str_starts_with((string) $route->getName(), 'guardian.')
            && str_starts_with(trim($route->uri(), '/').'/', $prefix.'/');
    }

    /**
     * `workbench:openapi {path}`: Scramble's export, made the same on every
     * machine and run. Scramble reads model columns when their tables exist,
     * so it reads a freshly migrated in-memory database rather than the
     * workbench one; the document is then rewritten with its keys sorted, so
     * diffs stay small. `composer client:generate` writes openapi.json with
     * it, and tests/Feature/OpenApiTest.php checks that file is current.
     */
    private function registerOpenApiCommand(): void
    {
        Artisan::command('workbench:openapi {path=openapi.json : Where to write the document}', function (): int {
            /** @var Command $this */
            config([
                'database.connections.openapi' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
                'database.default' => 'openapi',
            ]);

            DB::purge('openapi');

            if ($this->callSilently('migrate', ['--database' => 'openapi', '--force' => true]) !== Command::SUCCESS) {
                $this->error('The migrations failed.');

                return Command::FAILURE;
            }

            $path = (string) $this->argument('path');

            if ($this->callSilently('scramble:export', ['--path' => $path]) !== Command::SUCCESS) {
                $this->error('The export failed.');

                return Command::FAILURE;
            }

            $document = json_decode((string) file_get_contents($path), flags: JSON_THROW_ON_ERROR);

            file_put_contents($path, json_encode(WorkbenchServiceProvider::sortKeys($document), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

            $this->info("Exported Guardian's OpenAPI document to {$path}");

            return Command::SUCCESS;
        })->purpose("Export the OpenAPI document of Guardian's JSON API");
    }

    /**
     * Sorts every object's keys, recursively. Objects stay objects (an
     * empty schema is `{}`, never `[]`) and lists keep their order.
     */
    public static function sortKeys(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(self::sortKeys(...), $value);
        }

        if (! $value instanceof stdClass) {
            return $value;
        }

        $properties = get_object_vars($value);
        ksort($properties, SORT_STRING);
        $sorted = new stdClass;

        foreach ($properties as $key => $item) {
            $sorted->{(string) $key} = self::sortKeys($item);
        }

        return $sorted;
    }
}
