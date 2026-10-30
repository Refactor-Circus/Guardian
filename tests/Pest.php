<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Standards\Pest\ArchPreset;

uses(TestCase::class)->in(__DIR__);

ArchPreset::register();
