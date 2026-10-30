<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Console\Commands;

use Illuminate\Console\Command;

class GuardianCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'guardian:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package guardian.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Guardian placeholder command executed.');

        return self::SUCCESS;
    }
}
