<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Packages\CatalogSync\CatalogSynchronization;
use App\Packages\Referral\ReferralState;
use App\Packages\Utils\PGPUtils;
use App\Shop;
use App\Stat;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Mockery\Exception;
use PragmaRX\Google2FA\Google2FA;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    protected $synchronizer;

    /** @var ReferralState */
    protected $referralState;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CatalogSynchronization $synchronizer)
    {
        parent::__construct();
        $this->synchronizer = $synchronizer;
        $this->referralState = app('referral_state');
        $this->middleware('guest', ['except' => 'logout']);
        \View::share('page', 'login');
        $this->redirectTo = url($this->redirectTo);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $username = $request->get($this->username());

        if ($request->has('catalog_login') || starts_with_letter($username, '@')) { // bypass captcha
            return;
        }

        $this->validate($request, [
            $this->username() => 'required',
            'password' => 'required',
        ]);

        $cacheKey = $username . '_login_attempts';

        if (\Cache::get($cacheKey, 0) >= 3)  {
            $this->validate($request, [
                'captcha' => 'required|captcha'
            ]);
        }

        \Cache::add($cacheKey, 0, 30); // 30 minutes
        \Cache::increment($cacheKey);
    }

    /**
     * @inheritdoc
     */
    public function logout(Request $request)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }
        
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        if ($this->referralState->isReferralUrl && $this->referralState->isEnabled && $referral = $this->referralState->invitedBy) {
            $request->session()->put('referral', [
                'user_id' => $referral->id,
                'fee' => $this->referralState->fee
            ]);
        }

        return redirect('/auth/login', 303)->with('logout', true);
    }

    public function authenticated(Request $request, $user)
    {
        if ($request->has('redirect_after_login')) {
            try {
                $url = url($request->get('redirect_after_login'));
                \Session::put('url.intended', url($url));
            } catch (\Exception $e) {
                \Session::forget('url.intended');
            }
        }

        /** @var User $user */
        if ($user->totp_key) {
            \Auth::logout();
            \Session::put('2fa:totp:id', $user->id);
            return redirect('/auth/2fa/otp', 303);
        }

        if ($user->pgp_key) {
            \Auth::logout();
            \Session::put('2fa:pgp:id', $user->id);
            return redirect('/auth/2fa/pgp', 303);
        }

        $todayVisitors = \Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            \Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        return redirect()->to(url($this->redirectTo), 303);
    }

    public function show2FAOTPForm(Request $request)
    {
        if (!\Session::has('2fa:totp:id')) {
            return redirect('/');
        }

        return view('auth.2fa_otp');
    }

    public function login2FAOTP(Request $request, Google2FA $google2FA)
    {
        if (!\Session::has('2fa:totp:id')) {
            return redirect('/');
        }

        $this->validate($request, [
            'code' => 'required|digits:6'
        ]);

        $user = User::findOrFail(\Session::get('2fa:totp:id'));
        if (!$google2FA->verifyKey($user->totp_key, $request->get('code'))) {
            return redirect('/auth/2fa/otp')->with('invalid_code', true);
        }

        \Auth::login($user);

        $todayVisitors = \Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            \Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        return redirect()->to(url($this->redirectTo), 303);
    }

    public function show2FAPGPForm(Request $request)
    {
        if (!\Session::has('2fa:pgp:id')) {
            return redirect('/');
        }

        /** @var User $user */
        $user = User::findOrFail(\Session::get('2fa:pgp:id'));
        $code = Str::random();
        \Session::put('2fa:pgp:code', $code);

        $message = PGPUtils::encrypt($user->pgp_key, $code);

        return view('auth.2fa_pgp', [
            'message' => $message
        ]);
    }

    public function login2FAPGP(Request $request)
    {
        if (!\Session::has('2fa:pgp:id') || !\Session::has('2fa:pgp:code')) {
            return redirect('/');
        }

        $this->validate($request, [
            'code' => 'required'
        ]);

        $user = User::findOrFail(\Session::get('2fa:pgp:id'));
        if (\Session::pull('2fa:pgp:code') !== trim($request->get('code'))) {
            return redirect('/auth/2fa/pgp')->with('invalid_code', true);
        }

        \Auth::login($user);

        $todayVisitors = \Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            \Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        return redirect()->to(url($this->redirectTo), 303);
    }


    protected function sendFailedLoginResponse(Request $request)
    {
        $errors = [$this->username() => trans('auth.failed')];

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        // тепрерь все магазины по умолчанию подключены к каталогу
        $username = $request->get($this->username());
        if ($request->has('catalog_login') || starts_with_letter($username, '@')) {
            /*$shop = Shop::getDefaultShop();
            if ($shop->isCatalogSyncEnabled()) {
                return $this->catalogLogin($request);
            } else {
                $errors[$this->username()] = 'Магазин не подключен к каталогу Solaris.';
            }*/
            return $this->catalogLogin($request);
        }

        $cacheKey = $username . '_login_attempts';

        if (\Cache::get($cacheKey, 0) >= 3)  {
            $errors['captcha'] = 'we can show captcha now';
        }

        return redirect()->to('/auth/login', 303)
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function catalogLogin(Request $request)
    {
        $redirectURL = url('/');
        if ($request->has('redirect_after_login')) {
            $redirectURL = url($request->get('redirect_after_login'));
        } elseif (\Session::has('url.intended')) {
            $redirectURL = url(\Session::get('url.intended'));
        }

        $transparentAuthURL = $this->synchronizer->transparentAuthURL(
            ltrim($request->get($this->username()), '@'),
            $request->get('password'),
            $redirectURL
        );

        return redirect()->to($transparentAuthURL);
    }
}
