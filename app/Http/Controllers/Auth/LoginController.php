<?php
namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
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
            $authorizationUrl = $this->provider->getAuthorizationUrl(); // Generates state
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
        }
        $resourceOwner = json_decode(json_encode($this->provider->getResourceOwner($accessToken)->toArray()));

        if (
            ! isset($resourceOwner->data) ||
            ! isset($resourceOwner->data->cid) ||
            ! isset($resourceOwner->data->personal) ||
            ! isset($resourceOwner->data->vatsim) ||
            $resourceOwner->data->oauth->token_valid !== "true"
        ) {
            return redirect()->to('/')->withError("Please grant us your full name, email address, VATSIM details, country and continious access to access our services. You will be presented with our Data Protection Policy and ability to decline before the data will get stored with us.");
        }

        $account = $this->completeLogin($resourceOwner, $accessToken);

        Auth::login(User::find($resourceOwner->data->cid), false);

        $intended = $intended = Session::pull('url.intended', route('landing'));
        return redirect($intended);
    }

    protected function completeLogin($resourceOwner, $token)
    {

        $account = User::updateOrCreate(
            ['id' => $resourceOwner->data->cid],
            ['email' => $resourceOwner->data->personal->email,
            'first_name' => Controller::Windows1252ToUTF8($resourceOwner->data->personal->name_first),
            'last_name' => Controller::Windows1252ToUTF8($resourceOwner->data->personal->name_last),
            'rating' => $resourceOwner->data->vatsim->rating->id,
            'rating_short' => $resourceOwner->data->vatsim->rating->short,
            'rating_long' => $resourceOwner->data->vatsim->rating->long,
            'pilot_rating' => $resourceOwner->data->vatsim->pilotrating->id,
            'country' => $resourceOwner->data->personal->country->id,
            'region' => $resourceOwner->data->vatsim->region->id,
            'division' => $resourceOwner->data->vatsim->division->id,
            'subdivision' => $resourceOwner->data->vatsim->subdivision->id,
            'accepted_privacy' => 1,
            'last_login' => \Carbon\Carbon::now(),]
        );
        
        if ($resourceOwner->data->oauth->token_valid) { // User has given us permanent access to updated data
            $account->access_token = $token->getToken();
            $account->refresh_token = $token->getRefreshToken();
            $account->token_expires = $token->getExpires();
        }

        $account->save();

        return $account;
    }

    public function logout()
    {
        if (! Auth::check()) return redirect()->back();
        Auth::logout();
        return redirect()->to('/')->withSuccess('You have been successfully logged out.');
    }
}