<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Vatsim\VatsimConnect;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Session;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct(private readonly VatsimConnect $vatsim) {}

    public function login(Request $request)
    {
        if (! $request->has('code') || ! $request->has('state')) {
            ['url' => $url, 'state' => $state] = $this->vatsim->authorizationUrl();
            $request->session()->put('oauthstate', $state);

            return redirect()->away($url);
        }
        if ($request->input('state') !== session()->pull('oauthstate')) {
            return redirect()->to('/')->withError('Login state mismatch error. Please try again. If this persists, please contact technical department.');
        }

        return $this->verifyLogin($request);
    }

    protected function verifyLogin(Request $request)
    {
        try {
            $accessToken = $this->vatsim->exchangeCode($request->string('code')->value());
        } catch (IdentityProviderException $e) {
            return redirect()->to('/')->withError('Authentication error: '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed: '.$e::class.' '.$e->getMessage());

            return redirect()->to('/')->withError('Authentication error: '.$e->getMessage());
        }

        $resourceOwner = json_decode(json_encode($this->vatsim->resourceOwner($accessToken)));

        if (
            ! isset($resourceOwner->data) ||
            ! isset($resourceOwner->data->cid) ||
            ! isset($resourceOwner->data->personal) ||
            ! isset($resourceOwner->data->vatsim) ||
            ! isset($resourceOwner->data->personal->email) ||
            $resourceOwner->data->oauth->token_valid !== 'true'
        ) {
            return redirect()->to('/')->withError('Please grant us your full name, email address, VATSIM details, country and continious access to access our services. You will be presented with our Data Protection Policy before the data will get stored with us.');
        }

        if ($resourceOwner->data->vatsim->rating->id === 0) {
            return to_route('landing')->withError('<b>Login denied.</b><br>Login was denied because you are suspended from VATSIM.');
        }

        if ($resourceOwner->data->vatsim->rating->id === -1) {
            return to_route('landing')->withError('<b>Login denied.</b><br>Login was denied because your account is inactive.');
        }

        $user = User::find($resourceOwner->data->cid);

        // Check if user is banned
        if ($user && $user->banned) {
            return to_route('landing')->withError('<br>Login denied</br><br>User '.$user->id.' has been banned in '.\Config::get('app.owner_short').' for the following reason: <i>'.$user->banned->reason.'</i><br><br>For inquires contact '.\Config::get('app.owner_contact').'');
        }

        // Check if user exists and accepted privacy policy
        if ($user && $user->accepted_privacy) {
            return $this->completeLogin($resourceOwner, $accessToken);
        }
        session(['resourceOwner' => $resourceOwner, 'accessToken' => $accessToken]);

        // Store CERT data temporarily
        return to_route('dpp');
    }

    public function validatePrivacy(Request $get)
    {

        $resourceOwner = session('resourceOwner');
        $accessToken = session('accessToken');
        session()->forget('resourceOwner');
        session()->forget('accessToken');

        if (! $resourceOwner) {
            return to_route('landing')->withError('You need to authenticate yourself before accepting privacy policy');
        }

        return $this->completeLogin($resourceOwner, $accessToken);
    }

    protected function completeLogin($resourceOwner, $token): Redirector|RedirectResponse
    {
        User::updateOrCreate(
            ['id' => $resourceOwner->data->cid],
            ['email' => $resourceOwner->data->personal->email,
                'first_name' => ucfirst(mb_convert_encoding($resourceOwner->data->personal->name_first, 'UTF-8')),
                'last_name' => ucfirst(mb_convert_encoding($resourceOwner->data->personal->name_last, 'UTF-8')),
                'rating' => $resourceOwner->data->vatsim->rating->id,
                'rating_short' => $resourceOwner->data->vatsim->rating->short,
                'rating_long' => $resourceOwner->data->vatsim->rating->long,
                'pilot_rating' => $resourceOwner->data->vatsim->pilotrating->id,
                'pilot_rating_short' => $resourceOwner->data->vatsim->pilotrating->short,
                'pilot_rating_long' => $resourceOwner->data->vatsim->pilotrating->long,
                'country' => $resourceOwner->data->personal->country->id,
                'region' => $resourceOwner->data->vatsim->region->id,
                'division' => $resourceOwner->data->vatsim->division->id,
                'subdivision' => $resourceOwner->data->vatsim->subdivision->id,
                'accepted_privacy' => 1,
                'last_login' => Carbon::now(),
                'access_token' => $token->getToken(),
                'refresh_token' => $token->getRefreshToken(),
                'token_expires' => $token->getExpires(),
            ]
        );

        // Complete login and redirect to intended url
        Auth::login(User::find($resourceOwner->data->cid), false);

        $intended = Session::pull('url.intended', route('landing'));

        return redirect($intended);
    }
}
