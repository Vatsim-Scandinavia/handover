<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use App\Helpers\ConnectHelper;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\Controller;

#[Description('Command updates users data, so we keep our user information up to date.')]
#[Signature('member:data')]
class UpdateMemberData extends Command
{

    protected $connectHelper;
    protected $controller;

    /**
     * Create a new command instance.
     */
    public function __construct(ConnectHelper $connectHelper, Controller $controller)
    {
        parent::__construct();
        $this->connectHelper = $connectHelper;
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
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
                $user->first_name = ucfirst(mb_convert_encoding($response->data->personal->name_first, "UTF-8"));
                $user->last_name = ucfirst(mb_convert_encoding($response->data->personal->name_last, "UTF-8"));
                $user->rating = $response->data->vatsim->rating->id;
                $user->rating_short = $response->data->vatsim->rating->short;
                $user->rating_long = $response->data->vatsim->rating->long;
                $user->pilot_rating = $response->data->vatsim->pilotrating->id;
                $user->pilot_rating_short = $response->data->vatsim->pilotrating->short;
                $user->pilot_rating_long = $response->data->vatsim->pilotrating->long;
                $user->country = $response->data->personal->country->id;
                $user->region = $response->data->vatsim->region->id;
                $user->division = $response->data->vatsim->division->id;
                $user->subdivision = $response->data->vatsim->subdivision->id;
                $user->save();
                
            }
        }

    }
}
