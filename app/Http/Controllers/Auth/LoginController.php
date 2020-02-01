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
            $this->sso->validate(session('key'), session('secret'), $get->input('oauth_verifier'), function ($sso_data, $request) {
                session()->forget('key');
                session()->forget('secret');

                // Due to VATSIM not giving us true UTF-8 names, we need to search and fix the names which are not.
                function Windows1252ToUTF8($str){
                    if (preg_match('/Ã€|Ã|Ã‚|Ãƒ|Ã„|Ã…|Ã†|Ã‡|Ãˆ|Ã‰|ÃŠ|Ã‹|ÃŒ|Ã|ÃŽ|Ã|Ã|Ã‘|Ã’|Ã“|Ã”|Ã•|Ã–|Ã—|Ã˜|Ã™|Ãš|Ã›|Ãœ|Ã|Ãž|ÃŸ|Ã|Ã¡|Ã¢|Ã£|Ã¤|Ã¥|Ã¦|Ã§|Ã¨|Ã©|Ãª|Ã«|Ã¬|Ã|Ã®|Ã¯|Ã°|Ã±|Ã²|Ã³|Ã´|Ãµ|Ã¶|Ã·|Ã¸|Ã¹|Ãº|Ã»|Ã¼|Ã½|Ã¾|Ã¿/', $str)){
                        return mb_convert_encoding($str, "Windows-1252", "UTF-8");
                    }
                    return $str;
                }
            
                User::updateOrCreate([
                    'id' => $sso_data->id,
                    'email' => $sso_data->email,
                    'name' => Windows1252ToUTF8($sso_data->name_first)." ".Windows1252ToUTF8($sso_data->name_last),
                    'first_name' => Windows1252ToUTF8($sso_data->name_first),
                    'last_name' => Windows1252ToUTF8($sso_data->name_last),
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
                    'created_at' => \Carbon\Carbon::now(),
                ]);
                Auth::login(User::find($sso_data->id), true);
            });
        } catch (SSOException $e) {
            return redirect()->route('splash')->withErrors(['error' => $e->getMessage()]);
        }
        
        return redirect()->intended(route('splash'));
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