<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\KeenServiceProvider;

require_once __DIR__.'/helpers.php';

// Criterion 15, SCIM half: with Keen installed and guardian.audit.enabled on,
// the SCIM domain's events are recorded as package `guardian`, about the
// moved model, in its organization; switched off, nothing. Plus the SCIM case
// of Roster's tests/Modes/Keen/KeenAuditTest.php at the pinned SHA.

/**
 * Keen installed before Guardian boots, so the SCIM audit context registers.
 */
trait InstallsKeenForScimAuditEntries
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            KeenServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/vendor/refactor-circus/keen/database/migrations');
    }
}

uses(InstallsKeenForScimAuditEntries::class);

function keenScimEntry(string $action): AuditEntryModel
{
    return AuditEntryModel::query()->where('action', $action)->latest('id')->firstOrFail();
}

it('records SCIM changes with the token that made them, through the scim surface', function (): void {
    [, $this->scimToken] = scimOrg();

    scim('POST', '/Users', oktaUser())->assertCreated();

    $entry = keenScimEntry('user.created');

    expect($entry->surface)->toBe('scim')
        ->and($entry->context['scim_token']['name'])->toBe('Okta');
});

it('records an issued token as Guardian\'s, about the token, in its organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $token = app(CreateScimTokenAction::class)->execute($acme, new CreateScimTokenData(name: 'Okta'))->token;

    $entries = AuditEntryModel::query()->where('source', 'guardian')->where('subject_type', $token->getMorphClass())->get();

    expect($entries)->toHaveCount(1);

    $entry = $entries->sole();

    expect($entry->subject_id)->toBe((string) $token->getKey())
        ->and($entry->scope_type)->toBe($acme->getMorphClass())
        ->and($entry->scope_id)->toBe((string) $acme->getKey())
        ->and(json_encode($entry->toArray()))->not->toContain((string) $token->token_hash);
});

it('records nothing of the SCIM domain\'s while guardian.audit.enabled is off', function (): void {
    config()->set('guardian.audit.enabled', false);

    scimOrg();

    expect(AuditEntryModel::query()->where('source', 'guardian')->count())->toBe(0);
});
