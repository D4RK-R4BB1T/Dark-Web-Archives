<?php
/**
 * File: SettingsController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\Packages\Utils\PGPUtils;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class SettingsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        \View::share('page', 'settings');
    }

    public function index()
    {
        return redirect(url('/settings/security'));
    }

    public function showSecurityForm()
    {
        \View::share('section', 'security');
        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return view('settings.security.index_catalog');
        }

        return view('settings.security.index');
    }

    public function security(Request $request)
    {
        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return view('settings.security.index_catalog');
        }

        $this->validate($request, [
            'password' => 'required',
            'new_password' => 'required|min:6|confirmed',
            'new_password_confirmation' => 'required|min:6'
        ]);

        $user = \Auth::user();

        if (!\Hash::check($request->get('password'), $user->getAuthPassword())) {
            return redirect('/settings/security', 303)->with('flash_warning', 'Введенный текущий пароль неверен.');
        }

        $user->password = bcrypt($request->get('new_password'));
        $user->save();

        return redirect('/settings/security', 303)->with('flash_success', 'Настройки сохранены.');
    }

    public function showSecurity2FAOTPEnableForm(Request $request, Google2FA $google2FA)
    {
        if (\Auth::user()->totp_key || \Auth::user()->pgp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $totpKey = \Session::get('2fa:totp:key', $google2FA->generateSecretKey());
        \Session::put('2fa:totp:key', $totpKey);

        $imageURL = $google2FA->getQRCodeInline(transliterate(config('mm2.application_title')), transliterate(config('mm2.application_title')), $totpKey, 200);
        return view('settings.security.2fa_otp_enable', [
            'image' => $imageURL,
            'totpKey' => $totpKey
        ]);
    }

    public function security2FAOTPEnable(Request $request, Google2FA $google2FA)
    {
        if (\Auth::user()->totp_key || \Auth::user()->pgp_key || !\Session::has('2fa:totp:key')) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $this->validate($request, [
            'code' => 'required|digits:6'
        ]);

        $totpKey = \Session::get('2fa:totp:key');
        if (!$google2FA->verifyKey($totpKey, $request->get('code'))) {
            return redirect('/settings/security/2fa/otp/enable')->with('flash_warning', 'Введенный код неверен.');
        }

        $user = \Auth::user();
        $user->totp_key = $totpKey;
        $user->save();

        return redirect('/settings/security', 303)->with('flash_success', 'Двухфакторная авторизация включена.');
    }

    public function showSecurity2FAOTPDisableForm(Request $request)
    {
        if (!\Auth::user()->totp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        return view('settings.security.2fa_otp_disable');
    }

    public function security2FAOTPDisable(Request $request, Google2FA $google2FA)
    {
        if (!\Auth::user()->totp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $this->validate($request, [
            'code' => 'required|digits:6'
        ]);

        if (!$google2FA->verifyKey(\Auth::user()->totp_key, $request->get('code'))) {
            return redirect('/settings/security/2fa/otp/disable', 303)->with('flash_warning', 'Введенный код неверен.');
        }

        $user = \Auth::user();
        $user->totp_key = NULL;
        $user->save();

        return redirect('/settings/security', 303)->with('flash_success', 'Двухфакторная авторизация отключена.');
    }

    public function showSecurity2FAPGPEnableForm(Request $request)
    {
        if (\Auth::user()->totp_key || \Auth::user()->pgp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        return view('settings.security.2fa_pgp_enable');
    }

    public function security2FAPGPEnable(Request $request)
    {
        if (\Auth::user()->totp_key || \Auth::user()->pgp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $this->validate($request, [
            'pgp_key' => 'required|pgp_public_key'
        ]);

        $pgpKey = trim($request->get('pgp_key'));
        $code = Str::random();
        \Session::put('2fa:pgp:pgp_key', $pgpKey);
        \Session::put('2fa:pgp:code', $code);

        $message = PGPUtils::encrypt($pgpKey, $code);

        return view('settings.security.2fa_pgp_check', [
            'message' => $message
        ]);
    }

    public function security2FAPGPCheck(Request $request)
    {
        if (\Auth::user()->totp_key || \Auth::user()->pgp_key || !\Session::has('2fa:pgp:pgp_key')) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $this->validate($request, [
            'code' => 'required'
        ]);

        $code = trim($request->get('code'));
        if (\Session::get('2fa:pgp:code') !== $code) {
            return redirect('/settings/security/2fa/pgp/enable')->with('flash_warning', 'Проверка сообщения не удалась. Повторите попытку.');
        }

        $user = \Auth::user();
        $user->pgp_key = \Session::pull('2fa:pgp:pgp_key');
        $user->save();

        return redirect('/settings/security', 303)->with('flash_success', 'Двухфакторная авторизация включена.');
    }

    public function showSecurity2FAPGPDisableForm(Request $request)
    {
        if (!\Auth::user()->pgp_key) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $code = Str::random();
        \Session::put('2fa:pgp:code', $code);

        $message = PGPUtils::encrypt(\Auth::user()->pgp_key, $code);

        return view('settings.security.2fa_pgp_disable', [
            'message' => $message
        ]);
    }

    public function security2FAPGPDisable(Request $request)
    {
        if (!\Auth::user()->pgp_key || !\Session::has('2fa:pgp:code')) {
            return abort(403);
        }

        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return abort(403);
        }

        $this->validate($request, [
            'code' => 'required'
        ]);

        $code = trim($request->get('code'));
        if (\Session::pull('2fa:pgp:code') !== $code) {
            return redirect('/settings/security')->with('flash_warning', 'Проверка сообщения не удалась. Повторите попытку.');
        }

        $user = \Auth::user();
        $user->pgp_key = NULL;
        $user->save();

        return redirect('/settings/security', 303)->with('flash_success', 'Двухфакторная авторизация отключена.');
    }



    public function showContactsForm()
    {
        \View::share('section', 'contacts');
        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return view('settings.contacts.index_catalog');
        }
        return view('settings.contacts.index');
    }

    public function contacts(Request $request)
    {
        if (\Auth::user()->role === User::ROLE_CATALOG) {
            return view('settings.contacts.index_catalog');
        }

        $this->validate($request, [
            'contacts_jabber' => 'max:50',
            'contacts_telegram' => 'alpha_num',
            'contacts_other' => ''
        ]);

        $user = \Auth::user();
        $user->contacts_jabber = $request->get('contacts_jabber');
        $user->contacts_telegram = $request->get('contacts_telegram');
        $user->contacts_other = $request->get('contacts_other');
        $user->save();

        return redirect('/settings/contacts', 303)->with('flash_success', 'Настройки успешно сохранены.');
    }
}