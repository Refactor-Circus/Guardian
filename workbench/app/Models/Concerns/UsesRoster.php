<?php

namespace Workbench\App\Models\Concerns;

use RefactorCircus\Roster\Domains\User\Concerns\HasRoster;

// The `standalone` CI job uninstalls Roster, and the workbench user must still
// load there: it takes Roster's trait only when Roster is installed.
if (trait_exists(HasRoster::class)) {
    trait UsesRoster
    {
        use HasRoster;
    }
} else {
    trait UsesRoster {}
}
