<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Get the current application key')]
#[Signature('key:get')]
class KeyGet extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        $this->info('Current application key, copy the whole next line:');
        $this->info(config('app.key'));

        return Command::SUCCESS;
    }
}
