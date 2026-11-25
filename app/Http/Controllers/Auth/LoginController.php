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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

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

        $user = User::query()->find($member->cid);

        if ($user && $user->banned) {
            return to_route('landing')->withError('<br>Login denied</br><br>User '.$user->id.' has been banned in '.Config::get('app.owner_short').' for the following reason: <i>'.$user->banned->reason.'</i><br><br>For inquires contact '.Config::get('app.owner_contact').'');
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
        $user = User::query()->firstOrNew(['id' => $member->cid])
            ->fillFromVatsim($member)
            ->fill(['accepted_privacy' => 1, 'last_login' => Carbon::now()]);
        $user->vatsim_token = $token;
        $user->save();

        Auth::login($user, false);

        $intended = Session::pull('url.intended', route('landing'));

        return redirect($intended);
    }
}
