<?php

namespace App\Http\Requests\Admin;

use App\Admin;
use App\Events\Admin\UserGroupChanged;
use App\Log;
use App\Marketplace\Payment\FinalizeEarlyPayment;
use App\User;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ChangeUserGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'permissions' => 'array|nullable'
        ];
    }

    public function persist(User $user) {
        $permissions = $this -> permissions;
        if(!is_array($permissions)){
            $permissions = []; // empty array
        }

        $user -> setPermissions($permissions);
        $this->updateAdministraotr($user);
        $this->updateVendor($user);
        if ($user->vendor != null){
            $this->updateFinalizeEarly($user);
        }



        session()->flash('success', 'Successfully updated ' . $user->username . '\'s user groups and permissions');
    }

    /**
     *  If administrator flag is present and user is not administrator, make him one
     *  If administrator flag is not present, and user is administrator remove him admin
     *
     */
    public function updateAdministraotr(User $user) {

        // User is not admin, should change
        if ($this->administrator == 'adminChecked' && !$user->isAdmin()) {
            $nowTime = Carbon::now();
            Admin::insert([
                'id' => $user->id,
                'created_at' => $nowTime,
                'updated_at' => $nowTime
            ]);
            event(new UserGroupChanged($user, 'administrator', true, auth()->user()));
        }

        if ($this->administrator !== 'adminChecked' && $user->isAdmin()){
            $admin = Admin::find($user->id);
            if ($admin !== null) {
                $admin->delete();
                event(new UserGroupChanged($user, 'administrator', false, auth()->user()));
            }
        }
    }

    /**
     *  If vendor flag is present and user is not vendor, make him one
     *  If vendor flag is not present, and user is vendor remove him vendor acess
     *
     */
    public function updateVendor(User $user) {
        // User is not admin, should change
        if ($this->vendor == 'vendorChecked' && !$user->isVendor()) {
            Vendor::insert([
                'id' => $user -> id,
                'vendor_level' => 0,
                'about' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            event(new UserGroupChanged($user, 'vendor', true, auth()->user()));
        }

        if ($this->vendor !== 'vendorChecked' && $user->isVendor()){
            $vendor = Vendor::find($user->id);
            if ($vendor !== null) {
                $vendor->delete();
                event(new UserGroupChanged($user, 'vendor', false, auth()->user()));
            }
        }
    }
    
    public function updateFinalizeEarly(User $user){
        if (!FinalizeEarlyPayment::isEnabled())
            return;
        if($this->canUseFe == 'feChecked' && $user->isVendor() && !$user->vendor->canUseFe()){
            $user->vendor->can_use_fe = 1;
            $user->vendor->save();
        } else {
            $user->vendor->can_use_fe = 0;
            $user->vendor->save();
        }


    }
}
