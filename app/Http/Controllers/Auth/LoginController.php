<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Vatsim\IncompleteConsent;
use App\Vatsim\Member;
use App\Vatsim\VatsimConnect;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
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

        try {
            $member = $this->vatsim->member($accessToken);
        } catch (IncompleteConsent) {
            return redirect()->to('/')->withError('Please grant us your full name, email address, VATSIM details, country and continious access to access our services. You will be presented with our Data Protection Policy before the data will get stored with us.');
        }

        if ($member->isSuspended()) {
            return to_route('landing')->withError('<b>Login denied.</b><br>Login was denied because you are suspended from VATSIM.');
        }

        if ($member->isInactive()) {
            return to_route('landing')->withError('<b>Login denied.</b><br>Login was denied because your account is inactive.');
        }

        $user = User::find($member->cid);

        if ($user && $user->banned) {
            return to_route('landing')->withError('<br>Login denied</br><br>User '.$user->id.' has been banned in '.\Config::get('app.owner_short').' for the following reason: <i>'.$user->banned->reason.'</i><br><br>For inquires contact '.\Config::get('app.owner_contact').'');
        }

        if ($user && $user->accepted_privacy) {
            return $this->completeLogin($member, $accessToken);
        }
        // Store nothing until the member accepts the privacy policy.
        session(['vatsimMember' => $member, 'accessToken' => $accessToken]);

        return to_route('dpp');
    }

    public function validatePrivacy(Request $get)
    {

        $member = session()->pull('vatsimMember');
        $accessToken = session()->pull('accessToken');

        if (! $member instanceof Member || ! $accessToken instanceof AccessToken) {
            return to_route('landing')->withError('You need to authenticate yourself before accepting privacy policy');
        }

        return $this->completeLogin($member, $accessToken);
    }

    protected function completeLogin(Member $member, AccessToken $token): Redirector|RedirectResponse
    {
        User::updateOrCreate(
            ['id' => $member->cid],
            ['email' => $member->email,
                'first_name' => $member->firstName,
                'last_name' => $member->lastName,
                'rating' => $member->rating,
                'rating_short' => $member->ratingShort,
                'rating_long' => $member->ratingLong,
                'pilot_rating' => $member->pilotRating,
                'pilot_rating_short' => $member->pilotRatingShort,
                'pilot_rating_long' => $member->pilotRatingLong,
                'country' => $member->country,
                'region' => $member->region,
                'division' => $member->division,
                'subdivision' => $member->subdivision,
                'accepted_privacy' => 1,
                'last_login' => Carbon::now(),
                'access_token' => $token->getToken(),
                'refresh_token' => $token->getRefreshToken(),
                'token_expires' => $token->getExpires(),
            ]
        );

        // Complete login and redirect to intended url
        Auth::login(User::find($member->cid), false);

        $intended = Session::pull('url.intended', route('landing'));

        return redirect($intended);
    }
}
