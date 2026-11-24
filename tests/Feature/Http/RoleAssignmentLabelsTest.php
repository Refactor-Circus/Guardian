<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Role\Mcp\Tools\ListRoleAssignmentsTool;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Facades\Guardian;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;
use RefactorCircus\Keystone\Audit\AuditHooks;
use Workbench\App\Models\User;

/*
 * D-053: a role assignment names its user and scope for people to read
 * (`user_label`, `scope_label`) beside the ids actions use, resolved for any
 * host model and loaded in a fixed number of queries per page.
 */

final class FolderCodeLabel
{
    public function __invoke(FolderScope $folder): string
    {
        return 'Folder '.$folder->slug;
    }
}

it('names the user and the scope beside their ids', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    $folder = folder(['name' => 'Acme', 'slug' => 'w78504']);
    assignDirectly($ada, roleGranting('helper', []), $folder);
    assignDirectly($ada, roleGranting('global-helper', [], 'global'));

    $this->getJson(route('guardian.role-assignments.index', ['user' => $ada->getKey()]))
        ->assertOk()
        ->assertJsonPath('data.0.user_id', (string) $ada->getKey())
        ->assertJsonPath('data.0.user_label', 'Ada Lovelace')
        ->assertJsonPath('data.0.scope_id', $folder->id)
        ->assertJsonPath('data.0.scope_label', 'Acme')
        ->assertJsonPath('data.1.scope_type', null)
        ->assertJsonPath('data.1.scope_label', null);
});

it('labels the assignment it creates', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    $folder = folder(['name' => 'Acme']);

    $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'admin', 'scope_type' => FolderScope::class, 'scope_id' => $folder->id])
        ->assertCreated()
        ->assertJsonPath('data.user_label', 'Ada Lovelace')
        ->assertJsonPath('data.scope_label', 'Acme');
});

it('answers null labels for a user or scope that is gone, or a scope type no model answers to', function (): void {
    $ada = user();
    $folder = folder();
    $role = roleGranting('helper', []);
    assignDirectly($ada, $role, $folder);
    RoleAssignmentModel::query()->create(['role_id' => $role->id, 'user_id' => 999999, 'scope_type' => 'App\\Gone', 'scope_id' => 'x']);
    $folder->delete();

    $this->getJson(route('guardian.role-assignments.index'))
        ->assertOk()
        ->assertJsonPath('data.0.scope_label', null)
        ->assertJsonPath('data.1.user_id', '999999')
        ->assertJsonPath('data.1.user_label', null)
        ->assertJsonPath('data.1.scope_label', null);
});

it('lets the host name its models: a closure, then guardian.labels, then audit labels, then name-like attributes', function (): void {
    $ada = user(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $folder = folder(['name' => 'Acme', 'slug' => 'acme-hq']);
    assignDirectly($ada, roleGranting('helper', []), $folder);
    $first = fn (string $field): mixed => $this->getJson(route('guardian.role-assignments.index'))->assertOk()->json("data.0.{$field}");

    app(AuditHooks::class)->label(User::class, fn (User $user): string => 'Audit '.$user->email);
    expect($first('user_label'))->toBe('Audit ada@example.com');

    config(['guardian.labels' => [User::class => 'email', FolderScope::class => FolderCodeLabel::class]]);
    expect($first('user_label'))->toBe('ada@example.com')
        ->and($first('scope_label'))->toBe('Folder acme-hq');

    Guardian::label(FolderScope::class, fn (FolderScope $folder): string => strtoupper($folder->name));
    expect($first('scope_label'))->toBe('ACME');
});

it('loads a page\'s users and scopes in a fixed number of queries', function (): void {
    $count = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('guardian.role-assignments.index', ['per_page' => 50]))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $role = roleGranting('helper', []);
    assignDirectly(user(), $role, folder());
    $one = $count();

    foreach (range(1, 6) as $ignored) {
        assignDirectly(user(), $role, folder());
        assignDirectly(user(), $role);
    }

    expect($count())->toBe($one);
});

it('sends the labels over MCP too', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    assignDirectly($ada, roleGranting('helper', []), folder(['name' => 'Acme']));

    mcpTool(ListRoleAssignmentsTool::class, [])->assertOk()->assertSee('Ada Lovelace')->assertSee('Acme');
});
