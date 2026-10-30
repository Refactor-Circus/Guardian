<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

/*
 * Criterion 23 (models/stored-identifiers): once the handover is complete
 * (the suite's default), each moved model keeps its Roster class name as its
 * morph alias, so Keen entries stored under the old name resolve to
 * Guardian's model. tests/Modes/HandoverPending proves none are kept before.
 */

const ROSTER_CLASS_NAMES = [
    'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel' => SsoConnectionModel::class,
    'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoIdentityModel' => SsoIdentityModel::class,
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimTokenModel' => ScimTokenModel::class,
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimUserModel' => ScimUserModel::class,
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimGroupModel' => ScimGroupModel::class,
];

it('keeps each moved model\'s Roster class name as its morph alias', function (string $old, string $model): void {
    expect(Relation::getMorphedModel($old))->toBe($model)
        ->and((new $model)->getMorphClass())->toBe($old);
})->with(array_map(null, array_keys(ROSTER_CLASS_NAMES), array_values(ROSTER_CLASS_NAMES)));

it('resolves a Keen entry stored under Roster\'s SsoConnectionModel to Guardian\'s', function (): void {
    $this->app->register(KeenServiceProvider::class);
    $this->loadMigrationsFrom(dirname(__DIR__, 2).'/vendor/refactor-circus/keen/database/migrations');

    $organization = organization();
    $connection = SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    DB::table((new AuditEntryModel)->getTable())->insert([
        'source' => 'roster',
        'action' => 'sso_connection.created',
        'subject_type' => 'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel',
        'subject_id' => (string) $connection->getKey(),
        'surface' => 'http',
        'hash' => str_repeat('0', 64),
        'created_at' => now(),
    ]);

    $subject = AuditEntryModel::query()->where('source', 'roster')->where('action', 'sso_connection.created')->sole()->subject;

    expect($subject)->toBeInstanceOf(SsoConnectionModel::class)
        ->and($subject?->is($connection))->toBeTrue();
});
