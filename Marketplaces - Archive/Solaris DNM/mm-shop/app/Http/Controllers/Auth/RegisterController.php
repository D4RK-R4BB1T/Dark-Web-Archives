<?php

namespace App\Http\Controllers\Auth;

use App\AdvStatsCache;
use App\Packages\Referral\ReferralState;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/auth/pending';

    /** @var ReferralState */
    private $referralState;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->referralState = app('referral_state');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => 'required|min:4|max:14|not_starts_with_letter:@|not_starts_with_word:tg_|unique:users',
            'password' => 'required|min:6|confirmed',
            'captcha' => 'required|captcha',
//            'role' => 'required|in:' . implode(',', [User::ROLE_USER, User::ROLE_SHOP_PENDING])
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $referrerId = null;
        $referralFee = null;
        if ($this->referralState->isEnabled && $this->referralState->isReferralUrl) {
            $referrerId = $this->referralState->invitedBy->id;
            $referralFee = $this->referralState->fee;
        }
        return User::create([
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
            'role' => User::ROLE_USER, // $data['role']
            'referrer_id' => $referrerId,
            'referral_fee' => $referralFee
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);
        $this->registered($request, $user);

        return redirect($this->redirectPath(), 303);
    }

    /**
     * The user has been registered.
     */
    protected function registered(Request $request, User $user): void
    {
        $id = $request->cookie('advstats');
        if (is_numeric($id)) {
            AdvStatsCache::add($id, 0, 0, 1);
        }
    }
}
