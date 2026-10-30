<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\Modes\SsoUnavailable\SsoUnavailableTestCase;
use RefactorCircus\Guardian\Tests\RenamedTablesTestCase;
use RefactorCircus\Guardian\Tests\StandaloneTestCase;
use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Standards\Pest\ArchPreset;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

uses(TestCase::class)->in('Feature');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');
uses(SsoUnavailableTestCase::class)->in('Modes/SsoUnavailable');
// Also run by the `standalone` CI job, with Roster, Atrium and Keen
// uninstalled: nothing it loads may name them.
uses(StandaloneTestCase::class)->in('Modes/Standalone');

ArchPreset::register();

/**
 * Call a Guardian tool as an MCP client does, through tool search, with its
 * answer checked against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    return McpTools::call(GuardianServer::class, $tool, $arguments);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function user(array $attributes = []): User
{
    return UserFactory::new()->create($attributes);
}

/**
 * Give a user a Roster role by slug: global, in an organization, or on a team.
 */
function grant(User $user, string $slug, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
{
    $role = RoleModel::query()->where('slug', $slug)->firstOrFail();

    RoleAssignmentModel::query()->create([
        'role_id' => $role->getKey(),
        'user_id' => $user->getKey(),
        'organization_id' => $organization?->getKey() ?? $team?->organization_id,
        'team_id' => $team?->getKey(),
    ]);

    app(Permissions::class)->flush();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function organization(?User $owner = null, array $attributes = []): OrganizationModel
{
    return app(CreateOrganizationAction::class)->execute(CreateOrganizationData::from(['name' => $attributes['name'] ?? 'Acme'] + $attributes), $owner ?? user());
}

/**
 * Actions deliberately left off a surface, with the reason.
 *
 * @var array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Signing in and linking happen in the browser, between Guardian and the
    // identity provider; there is nothing for an API caller to send.
    'SsoLoginAction' => ['http', 'mcp'],
    'LinkSsoIdentityAction' => ['http', 'mcp'],
    // Temporary, "Atrium UI group": their screens land with Guardian's
    // Atrium plugin (task group 5), which removes these entries.
    'ListSsoConnectionsAction' => ['ui'],
    'ShowSsoConnectionAction' => ['ui'],
    'CreateSsoConnectionAction' => ['ui'],
    'UpdateSsoConnectionAction' => ['ui'],
    'DeleteSsoConnectionAction' => ['ui'],
    'ListSsoIdentitiesAction' => ['ui'],
    'UnlinkSsoIdentityAction' => ['ui'],
];

/**
 * Every PHP file under a directory, recursively; none when it does not exist.
 *
 * @return array<int, string>
 */
function phpFiles(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * Actions missing from the JSON API, MCP or a UI: Guardian's Atrium plugin
 * and screens, plus the browser pages a domain serves itself (SSO sign-in).
 *
 * @return array<string, array<int, string>>
 */
function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
        'ui' => [
            ...phpFiles($root.'/Atrium'),
            $root.'/Domains/*/Http/Controllers/*WebController.php',
        ],
    ], PARITY_EXCEPTIONS);
}
