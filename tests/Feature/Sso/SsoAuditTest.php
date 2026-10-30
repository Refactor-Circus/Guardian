<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

// Criterion 15: with Keen installed and guardian.audit.enabled on, creating a
// connection through its action is recorded as package `guardian`, with the
// connection as subject and its organization as scope; switched off, nothing.

beforeEach(function (): void {
    $this->app->register(KeenServiceProvider::class);
    $this->loadMigrationsFrom(dirname(__DIR__, 3).'/vendor/refactor-circus/keen/database/migrations');
});

function createAcmeConnection(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);

    $connection = app(CreateSsoConnectionAction::class)->execute($acme, new CreateSsoConnectionData(
        name: 'Okta',
        protocol: 'oidc',
        issuer: 'https://idp.test',
        clientId: 'app',
        clientSecret: 'top-secret',
    ));

    return [$acme, $connection];
}

it('records a created connection as Guardian\'s, about the connection, in its organization', function (): void {
    [$acme, $connection] = createAcmeConnection();

    $entries = AuditEntryModel::query()->where('source', 'guardian')->where('subject_type', $connection->getMorphClass())->get();

    expect($entries)->toHaveCount(1);

    $entry = $entries->sole();

    expect($entry->subject_id)->toBe((string) $connection->getKey())
        ->and($entry->scope_type)->toBe($acme->getMorphClass())
        ->and($entry->scope_id)->toBe((string) $acme->getKey())
        ->and(json_encode($entry->toArray()))->not->toContain('top-secret');
});

// The SSO half of Roster's tests/Modes/Keen "redacts passwords and connection
// secrets" at the pinned SHA.
it('redacts connection secrets', function (): void {
    [, $connection] = createAcmeConnection();

    app(UpdateSsoConnectionAction::class)->execute($connection, new UpdateSsoConnectionData(clientSecret: 'rotated'));

    $log = json_encode(AuditEntryModel::query()->get()->toArray());

    $updated = AuditEntryModel::query()->where('source', 'guardian')->where('action', 'sso_connection.updated')->latest('id')->firstOrFail();

    expect($log)->not->toContain('top-secret')
        ->and($log)->not->toContain('rotated')
        ->and($updated->changes['config'][1]['client_secret'])->toBe('[redacted]');
});

it('records nothing of Guardian\'s while guardian.audit.enabled is off', function (): void {
    config()->set('guardian.audit.enabled', false);

    createAcmeConnection();

    expect(AuditEntryModel::query()->where('source', 'guardian')->count())->toBe(0);
});
