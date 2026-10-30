<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Upgrade;

use Closure;

/**
 * Whether Roster has handed SSO and SCIM over to Guardian (D-022, D-027).
 *
 * Until refactor-circus/roster's `remove-sso-and-scim` release, Roster still
 * declares the SSO and SCIM tables, serves the same sign-in and SCIM URIs,
 * and owns the class names Keen entries were stored under. While it does,
 * Guardian loads neither its SSO web routes nor its SCIM server routes,
 * keeps no morph aliases and does not run the upgrade. Roster no longer
 * shipping its SSO connection model is the sign that it has let go. The
 * class is named as a string, so asking never loads it.
 *
 * The check is injected, so tests can stand in either side of the handover.
 */
final class Handover
{
    private const string ROSTER_SSO_CONNECTION = 'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel';

    /**
     * @var Closure(string): bool
     */
    private readonly Closure $classExists;

    /**
     * @param  (Closure(string): bool)|null  $classExists  whether a class exists; `class_exists()` by default
     */
    public function __construct(?Closure $classExists = null)
    {
        $this->classExists = $classExists ?? fn (string $class): bool => class_exists($class);
    }

    /**
     * Whether Roster no longer declares SSO and SCIM.
     */
    public function complete(): bool
    {
        return ! ($this->classExists)(self::ROSTER_SSO_CONNECTION);
    }
}
