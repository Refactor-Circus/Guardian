<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Guardian\Upgrade\Handover;

/**
 * Guardian beside a Roster that still ships SSO and SCIM (criterion 28): the
 * real Handover, which finds Roster's SSO connection model and so reports
 * the handover pending. Every Guardian migration runs, the upgrade included.
 *
 * Installed beside a Roster that has already handed SSO and SCIM over, the
 * real Handover would find the handover complete, so the Handover here
 * answers as it does beside a seams Roster: Roster's SSO connection model
 * exists. Its own class-exists check is still the one asked for every other
 * class name.
 *
 * Deleted with the Handover machinery once Roster `remove-sso-and-scim` is
 * tagged.
 */
abstract class HandoverPendingTestCase extends TestCase
{
    public const string ROSTER_SSO_CONNECTION = 'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel';

    /**
     * The morph map is static: forget what earlier suites' boots kept, so
     * this boot's own aliases are the only ones there.
     */
    protected function setUp(): void
    {
        Relation::morphMap([], false);

        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->instance(Handover::class, class_exists(self::ROSTER_SSO_CONNECTION)
            ? new Handover
            : new Handover(fn (string $class): bool => $class === self::ROSTER_SSO_CONNECTION || class_exists($class)));
    }

    /**
     * @return list<string>
     */
    protected function guardianMigrations(): array
    {
        return array_values(array_filter((array) glob(dirname(__DIR__).'/database/migrations/*.php'), is_string(...)));
    }
}
