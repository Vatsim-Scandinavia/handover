<?php

namespace App\Console\Commands;

use App\Helpers\ConnectHelper;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use App\Http\Controllers\Controller;

class UpdateMemberData extends Command
{

    protected $connectHelper;
    protected $controller;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'member:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command updates users data, so we keep our user information up to date.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ConnectHelper $connectHelper, Controller $controller)
    {
        parent::__construct();
        $this->connectHelper = $connectHelper;
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $users = User::query()->where('refresh_token', '!=', null)->get();

        foreach ($users as $user) {

            if (Carbon::parse($user->token_expires)->isPast()) {

                $refresh = $this->connectHelper->refreshToken($user);

                if (!$refresh) {

                    $user->access_token = null;
                    $user->refresh_token = null;
                    $user->token_expires = null;
                    $user->save();

                    continue;

                }

                $user->access_token = $refresh->access_token;
                $user->refresh_token = $refresh->refresh_token;
                $user->token_expires = now()->addSeconds($refresh->expires_in)->timestamp;
                $user->save();

            }
            

            $response = $this->connectHelper->fetchUser($user);

            if (collect($response)->isNotEmpty()) {

                $user->email = $response->data->personal->email;
                $user->first_name = (string)ucfirst($this->controller->Windows1252ToUTF8($response->data->personal->name_first));
                $user->last_name = (string)ucfirst($this->controller->Windows1252ToUTF8($response->data->personal->name_last));
                $user->rating = $response->data->vatsim->rating->id;
                $user->rating_short = $response->data->vatsim->rating->short;
                $user->rating_long = $response->data->vatsim->rating->long;
                $user->pilot_rating = $response->data->vatsim->pilotrating->id;
                $user->country = $response->data->personal->country->id;
                $user->region = $response->data->vatsim->region->id;
                $user->division = $response->data->vatsim->division->id;
                $user->subdivision = $response->data->vatsim->subdivision->id;
                $user->save();
                
            }
        }

    }
}
