<?php

namespace App\Http\Controllers\Admin;

use App\Ban;
use App\Exceptions\RequestException;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\ChangeBasicInfoRequest;
use App\Http\Requests\Admin\ChangeUserGroupRequest;
use App\Http\Requests\Admin\DisplayUsersRequest;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct() {
        $this->middleware('admin_panel_access');
    }

    /**
     * Checks the gate and returns 403 if not
     */
    private function checkGate(){
        if(Gate::denies('has-access', 'users'))
            abort(403);
    }

    public function users(DisplayUsersRequest $request) {
        $this -> checkGate();

        $request->persist();
        $users = $request->getUsers();
        return view('admin.users')->with([
            'users' => $users
        ]);
    }

    public function usersPost(Request $request){
        $this -> checkGate();


        return redirect()->route('admin.users',[
            'order_by' => $request->order_by,
            'display_group' => $request->display_group,
            'username' => $request -> username
        ]);
    }

    public function userView(User $user = null){
        $this -> checkGate();

        return view('admin.user')->with([
            'user' => $user
        ]);
    }

    public function editUserGroup(User $user,ChangeUserGroupRequest $request){
        $this -> checkGate();

        try{
            $request->persist($user);
        } catch(RequestException $e){
            session()->flash('error',$e->getMessage());
            return redirect()->back();
        }
        return redirect()->back();
    }

    public function editBasicInfo(User $user,ChangeBasicInfoRequest $request){
        $this -> checkGate();

        try{
            $request->persist($user);
        } catch(RequestException $e){
            session()->flash('error',$e->getMessage());
            return redirect()->back();
        }
        return redirect()->back();
    }

    /**
     * POST ban request
     *
     * @param User $user
     * @param BanUserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function banUser(User $user, BanUserRequest $request)
    {
        $this->checkGate();

        try{
            throw_if(auth()->user()->id==$user->id, new RequestException('You cannot ban yourself!'));
            throw_if($user->isAdmin(), new RequestException('You cannot ban admin!'));

            $user -> ban($request -> days);
            session()->flash('success', "You have successfully banned $user->username!");
        }
        catch (RequestException $e){
            session()->flash('errormessage',$e->getMessage());
            return redirect()->back();
        }
        return redirect()->back();

    }

    /**
     * Get request for removing ban
     *
     * @param Ban $ban
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function removeBan(Ban $ban)
    {
        $this -> checkGate();

        $ban -> delete();
        session()->flash('success', "You have successfully removed ban!");

        return redirect() -> back();
    }

}
