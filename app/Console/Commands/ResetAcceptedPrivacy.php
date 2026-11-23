<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Command description')]
#[Signature('reset:acceptedprivacy')]
class ResetAcceptedPrivacy extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        DB::table('users')->update(['accepted_privacy' => 0]);
        $this->info('All user\'s accepted privacy variable reseted in database!');

        return Command::SUCCESS;
    }
}
