<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAcceptedPrivacy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:acceptedprivacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        DB::table('users')->update(['accepted_privacy' => 0]);
        $this->info('All user\'s accepted privacy variable reseted in database!');

        return Command::SUCCESS;
    }
}
