<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\RequestException;
use App\Http\Requests\Auth\RecoverPasswordPgpRequest;
use App\Http\Requests\Auth\ResetPasswordPgpRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RecoverPasswordMnemonicRequest;

class ForgotPasswordController extends Controller
{
    public function showForget(){
        return view('auth.forgotpassword.forgotpassword');
    }

    public function showMnemonic(){
        return view('auth.forgotpassword.mnemonicpassword');
    }

    public function showPGP(){
        return view('auth.forgotpassword.pgppassword');
    }

    public function resetMnemonic(RecoverPasswordMnemonicRequest $request){
        try{
            return $request -> persist();
        } catch (RequestException $e){
            session() -> flash('errormessage', $e -> getMessage());
            return redirect()->back();
        }
    }

    public function sendVerify(RecoverPasswordPgpRequest $request){
        try{
            return $request -> persist();
        } catch (RequestException $e){
            session() -> flash('errormessage', $e -> getMessage());
            return redirect()->back();
        }
    }

    public function showVerify(){
        return view('auth.forgotpassword.pgppasswordverify');
    }

    public function resetPgp(ResetPasswordPgpRequest $request){
        try{
            return $request -> persist();
        } catch (RequestException $e){
            session() -> flash('errormessage', $e -> getMessage());
            return redirect()->back();
        }
    }

}
