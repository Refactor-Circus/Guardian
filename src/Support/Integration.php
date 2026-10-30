<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Closure;

/**
 * Which of the packages Guardian builds on are installed, and new enough.
 *
 * Guardian works on refactor-circus/roster's users and organizations through
 * Roster's seams, and adds its screens through refactor-circus/atrium's
 * screen extensions registry. Both are optional: without a Roster that has
 * the seams, Guardian registers nothing past its config, translations and
 * package entry; without an Atrium that has the registry, it adds nothing
 * to Atrium. Classes are named as strings, so asking never loads them.
 *
 * The checks are injected, so tests can stand in a package that is missing,
 * or one too old for Guardian.
 */
final class Integration
{
    private const string ROSTER = 'RefactorCircus\\Roster\\Roster';

    /**
     * The Roster methods Guardian calls.
     *
     * @var list<string>
     */
    private const array ROSTER_METHODS = ['requireSignIn', 'provisionedStatus'];

    /**
     * The Roster seams Guardian registers with.
     *
     * @var list<string>
     */
    private const array ROSTER_SEAMS = [
        'RefactorCircus\\Roster\\Domains\\Permission\\Services\\Resources',
        'RefactorCircus\\Roster\\Domains\\Permission\\Services\\PermissionCatalogue',
        'RefactorCircus\\Roster\\Domains\\Organization\\Services\\MembershipSources',
    ];

    private const string SCREEN_EXTENSIONS = 'RefactorCircus\\Atrium\\Domains\\Screens\\Services\\ScreenExtensions';

    /**
     * @var Closure(string): bool
     */
    private readonly Closure $classExists;

    /**
     * @var Closure(string, string): bool
     */
    private readonly Closure $methodExists;

    /**
     * @param  (Closure(string): bool)|null  $classExists  whether a class exists; `class_exists()` by default
     * @param  (Closure(string, string): bool)|null  $methodExists  whether a class has a method; `method_exists()` by default
     */
    public function __construct(?Closure $classExists = null, ?Closure $methodExists = null)
    {
        $this->classExists = $classExists ?? fn (string $class): bool => class_exists($class);
        $this->methodExists = $methodExists ?? fn (string $class, string $method): bool => method_exists($class, $method);
    }

    /**
     * Whether a Roster Guardian can work with is installed: one with the
     * sign-in and provisioning methods and the seams Guardian registers with.
     */
    public function rosterInstalled(): bool
    {
        if (! $this->rosterPresent()) {
            return false;
        }

        foreach (self::ROSTER_METHODS as $method) {
            if (! ($this->methodExists)(self::ROSTER, $method)) {
                return false;
            }
        }

        foreach (self::ROSTER_SEAMS as $seam) {
            if (! ($this->classExists)($seam)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether any Roster is installed, new enough for Guardian or not.
     */
    public function rosterPresent(): bool
    {
        return ($this->classExists)(self::ROSTER);
    }

    /**
     * Whether an Atrium with the screen extensions registry is installed.
     */
    public function atriumInstalled(): bool
    {
        return ($this->classExists)(self::SCREEN_EXTENSIONS);
    }
}
