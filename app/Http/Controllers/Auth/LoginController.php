<?php
namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Log;
use App\User;
use App\Http\Controllers\VatsimOAuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\PRatingsController;
use App\Http\Controllers\DivisionsController;
use App\Http\Controllers\TestController;
use League\OAuth2\Client\Token;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Session;

/**
 * Class AuthController
 * @package App\Http\Controllers\login
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $provider;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->provider = new VatsimOAuthController();
    }

    public function login(Request $request)
    {
        if (! $request->has('code') || ! $request->has('state')) {
            $authorizationUrl = $this->provider->getAuthorizationUrl([
                'required_scopes' => join(' ', config('vatsim_auth.scopes')),
            ]);
            $request->session()->put('oauthstate', $this->provider->getState());
			return redirect()->away($authorizationUrl);
        } else if ($request->input('state') !== session()->pull('oauthstate')) {
            return redirect()->to('/')->withError("Login state mismatch error. Please try again. If this persists, please contact Web Services.");
        } else {
            return $this->verifyLogin($request);
        }
    }

    protected function verifyLogin(Request $request)
    {
        try {
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->input('code')
            ]);
            
        } catch (IdentityProviderException $e) {
            return redirect()->to('/')->withError("Authentication error: ".$e->getMessage());
        } catch (OAuthServerException $e) {
            Log::critical("Error in OAuthServerException: ".$e);
            return redirect()->to('/')->withError("OAuth Authentication error: ".$e->getMessage());
        }

        $resourceOwner = json_decode(json_encode($this->provider->getResourceOwner($accessToken)->toArray()));

        if (
            ! isset($resourceOwner->data) ||
            ! isset($resourceOwner->data->cid) ||
            ! isset($resourceOwner->data->personal) ||
            ! isset($resourceOwner->data->vatsim) ||
            ! isset($resourceOwner->data->personal->email) ||
            $resourceOwner->data->oauth->token_valid !== "true"
        ) {
            return redirect()->to('/')->withError("Please grant us your full name, email address, VATSIM details, country and continious access to access our services. You will be presented with our Data Protection Policy before the data will get stored with us.");
        }
        
        if ($resourceOwner->data->vatsim->rating->id == 0) {
            return redirect()->route('landing')->withError('<b>Login denied.</b><br>Login was denied because you are suspended from VATSIM.');
        }

        if ($resourceOwner->data->vatsim->rating->id == -1) {
            return redirect()->route('landing')->withError('<b>Login denied.</b><br>Login was denied because your account is inactive.');
        }

        $user = User::find($resourceOwner->data->cid);

        // Check if user is banned
        if($user && $user->banned){
            return redirect()->route('landing')->withError('<br>Login denied</br><br>User '.$user->id.' has been banned in '.\Config::get('app.owner_short').' for the following reason: <i>'.$user->banned->reason.'</i><br><br>For inquires contact '.\Config::get('app.owner_contact').'');
        }

        // Check if user exists and accepted privacy policy                
        if($user && $user->accepted_privacy){
            return $this->completeLogin($resourceOwner, $accessToken);
        } else {
            session(['resourceOwner' => $resourceOwner, 'accessToken' => $accessToken]); // Store CERT data temporarily
            return redirect()->route('dpp');
        }

        
    }

    public function validatePrivacy(Request $get){

        $resourceOwner = session('resourceOwner');
        $accessToken = session('accessToken');
        session()->forget('resourceOwner');
        session()->forget('accessToken');

        if(!$resourceOwner){
            return redirect()->route('landing')->withError("You need to authenticate yourself before accepting privacy policy");
        }

        return $this->completeLogin($resourceOwner, $accessToken);
    }


    protected function completeLogin($resourceOwner, $token)
    {

        $account = User::updateOrCreate(
            ['id' => $resourceOwner->data->cid],
            ['email' => $resourceOwner->data->personal->email,
            'first_name' => ucfirst(mb_convert_encoding($resourceOwner->data->personal->name_first, "UTF-8")),
            'last_name' => ucfirst(mb_convert_encoding($resourceOwner->data->personal->name_last, "UTF-8")),
            'rating' => $resourceOwner->data->vatsim->rating->id,
            'rating_short' => $resourceOwner->data->vatsim->rating->short,
            'rating_long' => $resourceOwner->data->vatsim->rating->long,
            'pilot_rating' => $resourceOwner->data->vatsim->pilotrating->id,
            'country' => $resourceOwner->data->personal->country->id,
            'region' => $resourceOwner->data->vatsim->region->id,
            'division' => $resourceOwner->data->vatsim->division->id,
            'subdivision' => $resourceOwner->data->vatsim->subdivision->id,
            'accepted_privacy' => 1,
            'last_login' => \Carbon\Carbon::now(),
            'access_token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'token_expires' => $token->getExpires()
            ]
        );

        // Complete login and redirect to intended url
        Auth::login(User::find($resourceOwner->data->cid), false);

        $intended = $intended = Session::pull('url.intended', route('landing'));
        return redirect($intended);
    }

    public function logout()
    {
        if (! Auth::check()) return redirect()->back();
        Auth::logout();
        return redirect()->to('/')->withSuccess('You have been successfully logged out.');
    }
}
