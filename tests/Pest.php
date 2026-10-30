<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Standards\Pest\ArchPreset;

uses(TestCase::class)->in('Feature');

ArchPreset::register();

/**
 * Actions deliberately left off a surface, with the reason. The domain groups
 * add Roster's SSO entries as they move the actions.
 *
 * @var array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [];

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
