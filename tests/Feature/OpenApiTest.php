<?php

declare(strict_types=1);

use Dedoc\Scramble\ScrambleServiceProvider;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Workbench\App\Providers\WorkbenchServiceProvider;

/*
 * The committed OpenAPI document and TypeScript client (criteria 54 and 55):
 * both must match the API Guardian registers, so Atrium v2 and other clients
 * build on what the package serves. Regenerate them with
 * `composer client:generate`. Nothing here needs Node: the client is checked
 * through its stamp, and CI's `client` job rebuilds it.
 */

/**
 * Boots Scramble and the workbench provider, which holds the one Scramble
 * configuration and the `workbench:openapi` command `composer
 * client:generate` runs.
 */
trait ExportsGuardianOpenApi
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ScrambleServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }
}

uses(ExportsGuardianOpenApi::class);

const GUARDIAN_OPENAPI_REGENERATE = 'openapi.json is out of date: run `composer client:generate` and commit the result.';

const GUARDIAN_CLIENT_REGENERATE = 'The TypeScript client is out of date: run `composer client:generate` and commit the result.';

function guardianPackagePath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * @return array<string, mixed>
 */
function guardianJsonFile(string $path): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(guardianPackagePath($path)), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, mixed>
 */
function guardianOpenApi(): array
{
    return guardianJsonFile('openapi.json');
}

/**
 * Export the document as `composer client:generate` does (the workbench's
 * `workbench:openapi`, on a fresh in-memory database), into a temporary
 * file, and return its contents. It runs in this test's application, not a
 * `vendor/bin/testbench` process: that one writes a `.env` into the shared
 * Testbench skeleton while it runs, which parallel workers would load.
 */
function guardianExportedOpenApi(): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'guardian-openapi-');

    try {
        expect(Artisan::call('workbench:openapi', ['path' => $path]))->toBe(0, Artisan::output());

        return (string) file_get_contents($path);
    } finally {
        @unlink($path);
    }
}

/**
 * Every operation of the committed document, keyed by
 * "operationId METHOD /path".
 *
 * @return array<string, array<string, mixed>>
 */
function guardianDocumentedOperations(): array
{
    /** @var array<string, array<string, mixed>> $paths */
    $paths = guardianOpenApi()['paths'];
    $operations = [];

    foreach ($paths as $path => $item) {
        foreach ($item as $method => $operation) {
            if (in_array($method, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'], true)) {
                /** @var array<string, mixed> $operation */
                $operations[($operation['operationId'] ?? '(none)').' '.strtoupper($method).' '.$path] = $operation;
            }
        }
    }

    ksort($operations);

    return $operations;
}

/**
 * Every registered `guardian.*` route, keyed by name.
 *
 * @return array<string, RoutingRoute>
 */
function guardianNamedRoutes(): array
{
    Route::getRoutes()->refreshNameLookups();
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if ($route instanceof RoutingRoute && str_starts_with((string) $route->getName(), 'guardian.')) {
            $routes[(string) $route->getName()] = $route;
        }
    }

    return $routes;
}

/**
 * Every registered `guardian.*` JSON API route (under `guardian.routes.prefix`),
 * as "name METHOD /path" relative to the prefix the document's server carries.
 *
 * @return list<string>
 */
function guardianApiOperations(): array
{
    $prefix = trim((string) config('guardian.routes.prefix'), '/');
    $operations = [];

    foreach (guardianNamedRoutes() as $name => $route) {
        $uri = trim($route->uri(), '/');

        if (! str_starts_with($uri.'/', $prefix.'/')) {
            continue;
        }

        $uri = (string) preg_replace('#^'.preg_quote($prefix, '#').'(/|$)#', '', $uri);

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $operations[] = "{$name} {$method} /{$uri}";
        }
    }

    sort($operations);

    return $operations;
}

/**
 * Follow a local `$ref` (`#/components/...`) to the value it names.
 *
 * @return array<string, mixed>
 */
function guardianOpenApiRef(string $ref): array
{
    $value = guardianOpenApi();

    foreach (explode('/', substr($ref, 2)) as $key) {
        $value = is_array($value) ? ($value[$key] ?? []) : [];
    }

    return is_array($value) ? $value : [];
}

/**
 * The resources that declare their own `schema()` (the MCP output shape),
 * keyed by class basename.
 *
 * @return array<string, class-string>
 */
function guardianResourcesWithSchemas(): array
{
    $resources = [];

    foreach (glob(guardianPackagePath('src/Domains/*/Resources/*.php')) ?: [] as $file) {
        $relative = substr($file, strlen(guardianPackagePath('src/Domains/')), -4);
        $class = 'RefactorCircus\\Guardian\\Domains\\'.str_replace('/', '\\', $relative);

        if (method_exists($class, 'schema') && (new ReflectionMethod($class, 'schema'))->getDeclaringClass()->getName() === $class) {
            $resources[class_basename($class)] = $class;
        }
    }

    ksort($resources);

    return $resources;
}

it('matches a fresh export of the workbench', function (): void {
    expect(json_decode(guardianExportedOpenApi(), true, flags: JSON_THROW_ON_ERROR))
        ->toBe(guardianOpenApi(), GUARDIAN_OPENAPI_REGENERATE);
});

it('exports byte-identical documents twice in a row, from a clean in-memory database', function (): void {
    $first = guardianExportedOpenApi();

    expect($first)->toBe(guardianExportedOpenApi())
        ->and($first)->toBe((string) file_get_contents(guardianPackagePath('openapi.json')), GUARDIAN_OPENAPI_REGENERATE);
});

it('is an OpenAPI 3.1 document of the Guardian API 2.0.0, served under the route prefix', function (): void {
    $document = guardianOpenApi();

    expect((string) $document['openapi'])->toStartWith('3.1')
        ->and($document['info'])->toMatchArray(['title' => 'Guardian API', 'version' => '2.0.0'])
        ->and($document['servers'])->toBe([['url' => '/guardian']]);
});

it('documents every guardian API route once, under its route name', function (): void {
    $routes = guardianApiOperations();

    expect($routes)->toContain(
        'guardian.roles.index GET /roles',
        'guardian.permissions.index GET /permissions',
        'guardian.security-events.index GET /security-events',
        'guardian.two-factor.show GET /two-factor',
        'guardian.two-factor.passkeys.options POST /two-factor/passkeys/options',
        'guardian.sessions.index GET /sessions',
        'guardian.password-policy.show GET /password-policy',
        'guardian.tokens.store POST /tokens',
        'guardian.ip-rules.index GET /ip-rules',
        'guardian.organizations.sso-connections.index GET /organizations/{organization}/sso-connections',
        'guardian.organizations.scim-tokens.store POST /organizations/{organization}/scim-tokens',
    )->and(array_keys(guardianDocumentedOperations()))->toBe($routes);
});

it('documents no SCIM protocol route and no SSO web route', function (): void {
    $names = array_keys(guardianNamedRoutes());
    $operationIds = array_map(fn (string $operation): string => explode(' ', $operation)[0], array_keys(guardianDocumentedOperations()));

    expect($names)->toContain('guardian.scim.users.index', 'guardian.scim.groups.index', 'guardian.sso.start', 'guardian.sso.callback')
        ->and(preg_grep('/^guardian\.(scim|sso)\./', $operationIds))->toBe([])
        ->and(array_filter(array_keys((array) guardianOpenApi()['paths']), fn (string $path): bool => str_contains($path, 'Users') || str_contains($path, 'callback')))->toBe([]);
});

it('answers every operation with a typed JSON body, or no content', function (): void {
    foreach (guardianDocumentedOperations() as $key => $operation) {
        /** @var array<string, array<string, mixed>> $responses */
        $responses = $operation['responses'];
        $success = array_filter($responses, fn (string|int $status): bool => str_starts_with((string) $status, '2'), ARRAY_FILTER_USE_KEY);

        expect($success)->toHaveCount(1, $key);

        $status = (string) array_key_first($success);
        $schema = $success[$status]['content']['application/json']['schema'] ?? null;

        if ($status === '204') {
            expect($schema)->toBeNull($key);

            continue;
        }

        expect($schema)->toBeArray($key)
            ->and($schema['type'] ?? null)->toBe('object', $key)
            ->and($schema['properties'] ?? [])->not->toBeEmpty($key);
    }
});

it('names a summary for every operation', function (): void {
    foreach (guardianDocumentedOperations() as $key => $operation) {
        expect((string) ($operation['summary'] ?? ''))->not->toBe('', $key);
    }
});

it('pages the list operations with their meta and links', function (string $path, string $resource): void {
    $schema = guardianOpenApi()['paths'][$path]['get']['responses']['200']['content']['application/json']['schema'];

    expect(array_keys($schema['properties']))->toEqualCanonicalizing(['data', 'links', 'meta'])
        ->and($schema['properties']['data']['items'])->toBe(['$ref' => '#/components/schemas/'.$resource]);
})->with([
    ['/roles', 'RoleResource'],
    ['/permissions', 'PermissionResource'],
    ['/role-assignments', 'RoleAssignmentResource'],
    ['/security-events', 'SecurityEventResource'],
    ['/me/security-events', 'SecurityEventResource'],
    ['/sessions', 'SessionResource'],
    ['/devices', 'DeviceResource'],
    ['/two-factor-policies', 'TwoFactorPolicyResource'],
    ['/ip-rules', 'IpRuleResource'],
]);

it('pages the security log by cursor', function (): void {
    $meta = guardianOpenApi()['paths']['/security-events']['get']['responses']['200']['content']['application/json']['schema']['properties']['meta'];

    expect(array_keys($meta['properties']))->toContain('next_cursor', 'prev_cursor', 'per_page');
});

it('describes every resource with the fields its schema() declares', function (string $resource, string $class): void {
    $component = guardianOpenApiRef('#/components/schemas/'.$resource);
    /** @var array<string, mixed> $declared */
    $declared = $class::schema(new JsonSchemaTypeFactory);
    $variants = isset($component['anyOf']) ? (array) $component['anyOf'] : [$component];
    $properties = [];

    foreach ($variants as $variant) {
        $properties = [...$properties, ...array_keys((array) ($variant['properties'] ?? []))];
    }

    expect($component)->not->toBe([], "{$resource} is not a component of openapi.json")
        ->and(array_values(array_unique($properties)))->toEqualCanonicalizing(array_keys($declared));
})->with(function (): array {
    $cases = [];

    foreach (guardianResourcesWithSchemas() as $resource => $class) {
        $cases[$resource] = [$resource, $class];
    }

    return $cases;
});

it('types the nested resources and free-form fields', function (): void {
    $schemas = (array) guardianOpenApi()['components']['schemas'];

    expect($schemas['TwoFactorStatusResource']['properties']['methods']['items'])->toBe(['$ref' => '#/components/schemas/TwoFactorMethodResource'])
        ->and($schemas['TwoFactorStatusResource']['properties']['passkeys']['items'])->toBe(['$ref' => '#/components/schemas/PasskeyResource'])
        ->and($schemas['TotpEnrolmentResource']['properties']['method'])->toBe(['$ref' => '#/components/schemas/TwoFactorMethodResource'])
        ->and($schemas['RecoveryCodesResource']['properties']['recovery_codes'])->toBe(['items' => ['type' => 'string'], 'type' => 'array'])
        ->and($schemas['SecurityEventResource']['properties']['context']['type'])->toBe('object')
        ->and($schemas['ScimTokenResource']['properties']['base_url']['type'])->toBe(['string', 'null']);
});

it('answers a token value only when one is issued', function (): void {
    $paths = (array) guardianOpenApi()['paths'];

    expect(array_keys($paths['/tokens']['post']['responses']['201']['content']['application/json']['schema']['properties']))->toEqualCanonicalizing(['data', 'plain_text_token'])
        ->and(array_keys($paths['/organizations/{organization}/scim-tokens']['post']['responses']['201']['content']['application/json']['schema']['properties']))->toEqualCanonicalizing(['data', 'token'])
        ->and(guardianOpenApiRef('#/components/schemas/TokenResource')['properties'])->not->toHaveKey('plain_text_token');
});

it('is pretty-printed with sorted keys', function (): void {
    $sorted = function (mixed $value) use (&$sorted): bool {
        if (! is_array($value)) {
            return true;
        }

        if (! array_is_list($value)) {
            $keys = array_map(strval(...), array_keys($value));
            $expected = $keys;
            sort($expected, SORT_STRING);

            if ($keys !== $expected) {
                return false;
            }
        }

        return array_all($value, fn (mixed $item): bool => $sorted($item));
    };

    $contents = (string) file_get_contents(guardianPackagePath('openapi.json'));

    expect($contents)->toStartWith("{\n    \"")
        ->and($contents)->toEndWith("}\n")
        ->and($sorted(guardianOpenApi()))->toBeTrue();
});

it('holds no machine-specific value', function (): void {
    $json = (string) file_get_contents(guardianPackagePath('openapi.json'));

    expect($json)->not->toContain(guardianPackagePath())
        ->not->toContain('base64:')
        ->not->toContain('file://')
        ->not->toContain('localhost')
        ->not->toMatch('#/(Users|home|private|tmp|var/folders)/#');
});

/**
 * A file of the generated client, as a string.
 */
function guardianClientFile(string $path): string
{
    return (string) file_get_contents(guardianPackagePath('client/'.$path));
}

it('stamps the client with the committed openapi.json', function (): void {
    $stamp = trim(guardianClientFile('openapi.sha256'));
    $expected = hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents(guardianPackagePath('openapi.json'))));

    expect($stamp)->toBe($expected, GUARDIAN_CLIENT_REGENERATE);
});

it('writes the stamp with bin/client-stamp.php', function (): void {
    $source = (string) file_get_contents(guardianPackagePath('bin/client-stamp.php'));

    expect($source)->toContain("'/openapi.json'")
        ->toContain("'/client/openapi.sha256'")
        ->toContain("hash('sha256'");
});

it('commits the generated schema and the built client', function (string $path): void {
    expect(guardianPackagePath('client/'.$path))->toBeFile();
})->with([
    'src/schema.d.ts',
    'src/index.ts',
    'dist/index.js',
    'dist/index.d.ts',
    'package.json',
    'package-lock.json',
    'tsconfig.json',
    'openapi.sha256',
]);

it('generates the schema from every documented path', function (): void {
    $schema = guardianClientFile('src/schema.d.ts');

    expect($schema)->toContain('export interface paths', 'export interface components');

    foreach (array_keys((array) guardianOpenApi()['paths']) as $path) {
        expect(str_contains($schema, '"'.$path.'": {'))->toBeTrue(GUARDIAN_CLIENT_REGENERATE.' Missing: '.$path);
    }
});

it('publishes @refactor-circus/guardian-client 2.0.0 as an esm package built into dist', function (): void {
    $manifest = guardianJsonFile('client/package.json');

    expect($manifest['name'])->toBe('@refactor-circus/guardian-client')
        ->and($manifest['version'])->toBe('2.0.0')
        ->and($manifest['type'])->toBe('module')
        ->and($manifest['types'])->toBe('./dist/index.d.ts')
        ->and($manifest['exports'])->toBe([
            '.' => [
                'types' => './dist/index.d.ts',
                'import' => './dist/index.js',
            ],
        ]);
});

it('depends on openapi-fetch only and pins its generators exactly', function (): void {
    $manifest = guardianJsonFile('client/package.json');
    $devDependencies = (array) $manifest['devDependencies'];

    expect(array_keys((array) $manifest['dependencies']))->toBe(['openapi-fetch'])
        ->and($devDependencies)->toHaveKeys(['openapi-typescript', 'typescript'])
        ->and($devDependencies)->not->toHaveKey('openapi-fetch');

    foreach (['openapi-typescript', 'typescript'] as $package) {
        expect((string) $devDependencies[$package])->toMatch('/^\d+\.\d+\.\d+$/');
    }
});

it('generates the schema from openapi.json, builds with tsc and type-checks', function (): void {
    expect((array) guardianJsonFile('client/package.json')['scripts'])->toMatchArray([
        'generate' => 'openapi-typescript ../openapi.json -o src/schema.d.ts --empty-objects-unknown && tsc -p tsconfig.json',
        'typecheck' => 'tsc -p tsconfig.json --noEmit',
    ]);
});

it('exports createGuardianClient, GuardianClient and the schema types', function (string $file): void {
    $source = guardianClientFile($file);

    expect($source)->toMatch('/export (declare )?function createGuardianClient\(options: GuardianClientOptions\)/')
        ->toMatch('/export type GuardianClientOptions = ClientOptions & \{\s*baseUrl: string;\s*\}/')
        ->toMatch('/export type GuardianClient\b/')
        ->toMatch('/export type \{[^}]*\bpaths\b[^}]*\}/')
        ->toMatch('/export type \{[^}]*\bcomponents\b[^}]*\}/');
})->with(['src/index.ts', 'dist/index.d.ts']);

it('builds createGuardianClient on openapi-fetch', function (): void {
    expect(guardianClientFile('dist/index.js'))
        ->toContain("import createClient from 'openapi-fetch';")
        ->toContain('export function createGuardianClient(options) {');
});

it('regenerates through composer client:generate, with Scramble for the workbench only', function (): void {
    $composer = guardianJsonFile('composer.json');

    /** @var array<string, array<int, string>> $scripts */
    $scripts = $composer['scripts'];

    expect($composer['require-dev'])->toHaveKey('dedoc/scramble')
        ->and($composer['require'])->not->toHaveKey('dedoc/scramble')
        ->and($composer['suggest'] ?? [])->not->toHaveKey('dedoc/scramble')
        ->and($scripts['client:generate'])->toBe([
            '@php vendor/bin/testbench workbench:openapi openapi.json',
            'npm --prefix client ci',
            'npm --prefix client run generate',
            '@php bin/client-stamp.php',
        ]);
});

it('checks the document and the client in CI', function (): void {
    $workflow = (string) file_get_contents(guardianPackagePath('.github/workflows/tests.yml'));

    expect($workflow)->toContain('  client:')
        ->and($workflow)->toContain('composer client:generate')
        ->and($workflow)->toContain('npm --prefix client run typecheck')
        ->and($workflow)->toContain('git diff --exit-code -- openapi.json client');
});

it('ships openapi.json and the client, and keeps client dependencies out of git', function (): void {
    $attributes = (string) file_get_contents(guardianPackagePath('.gitattributes'));
    $ignored = array_map(trim(...), file(guardianPackagePath('.gitignore')) ?: []);

    expect($attributes)->not->toMatch('#^/?(client|openapi\.json|bin)\b.*export-ignore#m')
        ->and($ignored)->toContain('/client/node_modules')
        ->and($ignored)->not->toContain('/client/dist');
});
