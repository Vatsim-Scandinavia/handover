<?php
namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Vatsim\OAuth\SSOException;
use Vatsim\OAuth\SSO;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\PRatingsController;
use App\Http\Controllers\DivisionsController;
use App\Http\Controllers\TestController;
use App\User;
/**
 * Class AuthController
 * @package App\Http\Controllers\login
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;
    /**
     * @var SSO
     */
    private $sso;
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->sso = new SSO(
            config('sso.base'),
            config('sso.key'),
            config('sso.secret'),
            config('sso.method'),
            config('sso.cert'),
            config('sso.additionalConfig')
        );
    }
    /**
     * Redirect user to VATSIM SSO for login
     *
     * @throws \Vatsim\OAuth\SSOException
     */
    public function login()
    {
        try{
            $this->sso->login(config('sso.return'), function ($key, $secret, $url) {
                session()->put('key', $key);
                session()->put('secret', $secret);
                session()->save();
                header('Location: ' . $url);
                die();
            });
        } catch (SSOException $e) {
            return redirect()->route('splash')->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate the login and access protected resources, create the user if they don't exist, update them if they do, and log them in
     *
     * @param Request $get
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Vatsim\OAuth\SSOException
     */
    public $newUser;
    public function validateLogin(Request $get)
    {
        try{
            $showPrivacy = $this->sso->validate(session('key'), session('secret'), $get->input('oauth_verifier'), function ($sso_data, $request) {
                session()->forget('key');
                session()->forget('secret');

                // Check if user exists and if accepted privacy policy
                $user = User::find($sso_data->id);
                if($user && $user->accepted_privacy){
                    $this->loginHandover($sso_data);
                    return false;
                } else {
                    session(['sso_data' => $sso_data]);
                    return true;
                }

            });
        } catch (SSOException $e) {
            return redirect()->route('splash')->withErrors(['error' => $e->getMessage()]);
        }
        
        // Redirect based on if the user needs to accept DPP or not
        if($showPrivacy) {
            return redirect()->intended(route('privacy'));
        }
        return redirect()->intended(route('splash'));
    }

    public function validatePrivacy(Request $get){

        $sso_data = session('sso_data');

        if(!$sso_data){
            return redirect()->route('splash')->withErrors(['error' => "You need to authenticate yourself before accepting privacy policy"]);
        }

        session()->forget('sso_data');
        $this->loginHandover($sso_data);

        return redirect()->intended(route('splash'));
    }

    public function loginHandover($sso_data){
        User::updateOrCreate(
            ['id' => $sso_data->id],
            ['email' => $sso_data->email,
            'full_name' => Controller::Windows1252ToUTF8($sso_data->name_first)." ".Controller::Windows1252ToUTF8($sso_data->name_last),
            'first_name' => Controller::Windows1252ToUTF8($sso_data->name_first),
            'last_name' => Controller::Windows1252ToUTF8($sso_data->name_last),
            'rating' => $sso_data->rating->id,
            'rating_short' => $sso_data->rating->short,
            'rating_long' => $sso_data->rating->long,
            'rating_grp' => $sso_data->rating->GRP,
            'pilot_rating' => $sso_data->pilot_rating->rating,
            'country' => $sso_data->country->code,
            'region' => $sso_data->region->code,
            'division' => $sso_data->division->code,
            'subdivision' => $sso_data->subdivision->code,
            'active' => 0,
            'accepted_privacy' => 1,
            'created_at' => \Carbon\Carbon::now(),]
        );
        Auth::login(User::find($sso_data->id), true);
    }

    /**
     * Log the user out
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout()
    {
        if (! Auth::check()) return redirect()->back();
        Auth::logout();
        return redirect()->to('/')->withSuccess('You have been successfully logged out.');
    }
}