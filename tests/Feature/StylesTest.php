<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Testing\AtriumStyles;

// Criterion 20: Guardian ships no stylesheet. Its views use Atrium's
// components and the utilities Atrium's stylesheet compiles, nothing else.

const GUARDIAN_VIEWS = __DIR__.'/../../resources/views';

it('ships the Atrium views', function (string $view): void {
    expect(GUARDIAN_VIEWS.'/'.$view)->toBeFile();
})->with([
    'atrium/organization-sso.blade.php',
    'atrium/organization-scim.blade.php',
    'atrium/user-sso-identities.blade.php',
    'atrium/sso-connection.blade.php',
    'atrium/partials/sso-fields.blade.php',
]);

it('uses only classes Atrium\'s stylesheet compiles', function (): void {
    expect(AtriumStyles::missingClasses(GUARDIAN_VIEWS))->toBe([]);
});

it('has no style blocks or style attributes', function (): void {
    expect(AtriumStyles::inlineStyles(GUARDIAN_VIEWS))->toBe([]);
});

it('ships no stylesheet and no component namespace of its own', function (): void {
    $root = dirname(__DIR__, 2);

    expect(glob($root.'/resources/css/*') ?: [])->toBe([])
        ->and(is_dir($root.'/resources/views/components'))->toBeFalse()
        ->and((string) file_get_contents($root.'/src/GuardianServiceProvider.php'))->not->toContain('Blade::component');
});

it('takes every string from Guardian\'s translations', function (): void {
    $offenders = [];

    foreach (glob(GUARDIAN_VIEWS.'/atrium/{,partials/}*.blade.php', GLOB_BRACE) ?: [] as $view) {
        $contents = (string) file_get_contents($view);

        if (str_contains($contents, 'roster::') || preg_match('/__\(\'(?!guardian::guardian\.)/', $contents) === 1) {
            $offenders[] = basename($view);
        }
    }

    expect($offenders)->toBe([]);
});
