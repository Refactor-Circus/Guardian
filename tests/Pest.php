<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\SearchTools;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Tests\HandoverPendingTestCase;
use RefactorCircus\Guardian\Tests\Modes\SsoUnavailable\SsoUnavailableTestCase;
use RefactorCircus\Guardian\Tests\RenamedTablesTestCase;
use RefactorCircus\Guardian\Tests\StandaloneTestCase;
use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Standards\Pest\ArchPreset;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

// No test may reach the network: the breach check (HIBP) and every other
// HTTP call must be faked (criterion 40).
uses(TestCase::class)->beforeEach(fn () => Http::preventStrayRequests())->in('Feature');

uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');
uses(SsoUnavailableTestCase::class)->in('Modes/SsoUnavailable');
uses(HandoverPendingTestCase::class)->in('Modes/HandoverPending');
// Also run by the `standalone` CI job, with Roster and Keen uninstalled:
// nothing it loads may name them.
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
 * The names of every tool in GuardianServer's catalog, browsed as an MCP
 * client browses it through `search_tools`: that answers at most 50 a call
 * and has no offset, so once a page says there is more, the client pages on
 * by searching each word of the tool names it has seen ("list", "role",
 * "scim", ...), 50 at a time, until no search turns up a tool it has not
 * seen. Names are kept in the order they were first seen: the first page is
 * the catalog's own order.
 *
 * @return list<string>
 */
function toolCatalog(): array
{
    $search = (new GuardianServer(new FakeTransporter))->createContext()->tools()
        ->first(fn (object $tool): bool => $tool instanceof SearchTools);

    expect($search)->toBeInstanceOf(SearchTools::class);

    /** @var SearchTools $search */
    $page = function (string $query) use ($search): array {
        /** @var array{tools: list<array{name: string}>, hasMore: bool} $result */
        $result = json_decode((string) $search->handle(new Request(['query' => $query, 'limit' => 50]))->content(), true, 512, JSON_THROW_ON_ERROR);

        return $result;
    };

    $first = $page('');
    $names = array_column($first['tools'], 'name');

    if (! $first['hasMore']) {
        return $names;
    }

    $searched = [];

    do {
        $found = false;

        foreach ($names as $name) {
            foreach (explode('-', $name) as $term) {
                if ($term === 'tool' || isset($searched[$term])) {
                    continue;
                }

                $searched[$term] = true;

                foreach (array_column($page($term)['tools'], 'name') as $match) {
                    if (! in_array($match, $names, true)) {
                        $names[] = $match;
                        $found = true;
                    }
                }
            }
        }
    } while ($found);

    return $names;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function user(array $attributes = []): User
{
    return UserFactory::new()->create($attributes);
}

/**
 * Give a user a role by slug, globally, in an organization, or on a team,
 * through Guardian's role store (bound as Keystone's Roles).
 */
function grant(User $user, string $slug, ?Model $organization = null, ?Model $team = null): void
{
    expect(app(Roles::class)->assign($user, $slug, $team ?? $organization))->toBeTrue("No {$slug} role there");
}

/**
 * @param  array<string, mixed>  $attributes
 */
function organization(?User $owner = null, array $attributes = []): OrganizationModel
{
    return app(CreateOrganizationAction::class)->execute(CreateOrganizationData::from(['name' => $attributes['name'] ?? 'Acme'] + $attributes), $owner ?? user());
}

/**
 * One of Roster's moved MCP tools as a client saw it at the pinned SHA
 * (tests/moved-tests.json), captured once into tests/Fixtures because the
 * Roster installed here may no longer ship it, with permission names written
 * Guardian's way.
 *
 * @return array<string, mixed>
 */
function pinnedRosterTool(string $class): array
{
    /** @var array<string, array<string, mixed>> $tools */
    $tools = json_decode((string) file_get_contents(__DIR__.'/Fixtures/roster-6b1833d/mcp-tools.json'), true, 512, JSON_THROW_ON_ERROR);

    expect($tools)->toHaveKey($class);

    /** @var array<string, mixed> */
    return json_decode(str_replace(['roster.sso.', 'roster.scim.'], ['guardian.sso.', 'guardian.scim.'], (string) json_encode($tools[$class])), true);
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
    // Only Guardian writes the security log, from the events it observes;
    // nobody may add to it over the API or MCP.
    'RecordSecurityEventAction' => ['http', 'mcp'],
    // Enrolment answers a TOTP secret, and regeneration recovery codes:
    // secrets an agent must never be handed (D-021).
    'StartTotpEnrolmentAction' => ['mcp'],
    'ConfirmTotpAction' => ['mcp'],
    'RegenerateRecoveryCodesAction' => ['mcp'],
    // Enrolling an email address or phone number sends a code an agent
    // would then read back (D-021).
    'StartOneTimeCodeEnrolmentAction' => ['mcp'],
    'ConfirmOneTimeCodeAction' => ['mcp'],
    // A passkey ceremony happens between the browser and its
    // authenticator; there is nothing an agent could send (D-021).
    'StartPasskeyRegistrationAction' => ['mcp'],
    'RegisterPasskeyAction' => ['mcp'],
    // The challenge confirms a browser session: an agent has none (D-021).
    'PassChallengeAction' => ['mcp'],
    'SendChallengeCodeAction' => ['mcp'],
    'StartPasskeyChallengeAction' => ['mcp'],
    // Checking a password sends it: an agent must never be handed one, nor
    // send one (D-021).
    'CheckPasswordAction' => ['mcp'],
    // Issuing answers a usable API token: an agent must never receive one
    // (D-017, D-021).
    'IssueTokenAction' => ['mcp'],
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
 * Actions missing from the JSON API or MCP.
 *
 * @return array<string, array<int, string>>
 */
function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
    ], PARITY_EXCEPTIONS);
}
