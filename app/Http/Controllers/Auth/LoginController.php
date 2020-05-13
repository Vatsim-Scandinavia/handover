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
use Session;

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
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request){

        if (!Auth::check()) {
            $this->loginSSO();
        }

        return redirect()->intended(route('landing'));
    }

    /**
     * Redirect user to VATSIM SSO for login
     *
     * @throws \Vatsim\OAuth\SSOException
     */
    public function loginSSO()
    {
        try{
            return $this->sso->login(config('sso.return'), function ($key, $secret, $url) {
                session()->put('key', $key);
                session()->put('secret', $secret);
                session()->save();
                header('Location: ' . $url);
                die();
            });
        } catch (SSOException $e) {
            return redirect()->back()->withError("We were unable to contact VATSIM's certification service. Please try again later. If this persists, please contact Web Services. " . $e->getMessage());
        }
    }

    /**
     * Validate the login and access protected resources, create the user if they don't exist, update them if they do, and log them in
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Vatsim\OAuth\SSOException
     */
    public $newUser;
    public function validateLogin(Request $request)
    {
        try{
            return $this->sso->validate(session('key'), session('secret'), $request->input('oauth_verifier'), function ($sso_data, $request) {
                session()->forget('key');
                session()->forget('secret');

                $user = User::find($sso_data->id);
                // Check if user is banned
                if($user && $user->banned){
                    return redirect()->route('landing')->withError('User '.$user->id.' has been banned in '.env('APP_VACC').' for the following reason: <i>'.$user->banned->reason.'</i><br><br>For inquires contact '.env('APP_VACC_CONTACT').'');
                }

                // Check if user exists and accepted privacy policy
                
                if($user && $user->accepted_privacy){
                    return $this->vatsimSsoValidationSuccess($sso_data);
                } else {
                    session(['sso_data' => $sso_data]);
                    return redirect()->route('dpp');
                }

            });
        } catch (SSOException $e) {
            return redirect()->route('landing')->withError('Could not authenticate: '.$e->getMessage());
        }
    }

    public function validatePrivacy(Request $get){

        $sso_data = session('sso_data');
        session()->forget('sso_data');

        if(!$sso_data){
            return redirect()->route('landing')->withError("You need to authenticate yourself before accepting privacy policy");
        }

        return $this->vatsimSsoValidationSuccess($sso_data);
    }

    public function vatsimSsoValidationSuccess($sso_data){

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
            'accepted_privacy' => 1,
            'reg_date' => $sso_data->reg_date,
            'last_login' => \Carbon\Carbon::now(),]
        );

        Auth::login(User::find($sso_data->id), false);

        $intended = $intended = Session::pull('url.intended', route('landing'));
        return redirect($intended);
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