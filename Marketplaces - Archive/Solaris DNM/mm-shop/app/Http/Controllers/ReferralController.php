<?php


namespace App\Http\Controllers;


use App\Packages\Referral\ReferralState;
use App\ReferralUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ReferralController extends Controller
{
    /** @var ReferralState */
    private $referralState;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        \View::share('page', 'referral');
        $this->referralState = app('referral_state');
        if (!$this->referralState->isEnabled || $this->referralState->isReferralUrl) {
            die();
        }
    }

    public function index()
    {
        return redirect('/referral/url');
    }

    public function showUrlForm(Request $request)
    {
        \View::share('section', 'url');
        $referralUrls = \Auth::user()->referralUrls()->paginate(20);
        return view('referral.url.index', ['urls' => $referralUrls]);
    }

    public function url(Request $request)
    {
        $this->validate($request, [
            'fee' => 'required|numeric|min:0.1|max:100'
        ]);

        $fee = $request->get('fee');
        if (\Auth::user()->referralUrls()->where('fee', $fee)->exists()) {
            return redirect()->back()->with('flash_warning', 'Ссылка с такой комиссией уже существует.');
        }

        ReferralUrl::create([
            'user_id' => \Auth::id(),
            'slug' => Str::random(),
            'fee' => $fee
        ]);

        return redirect('/referral/url')->with('flash_success', 'Ссылка успешно создана.');
    }
}