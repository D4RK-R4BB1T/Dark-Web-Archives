<?php
/**
 * File: TransparentController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Packages\CatalogSync\CatalogSynchronization;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Mockery\Exception;

class TransparentController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request, CatalogSynchronization $synchronizer)
    {
        try {
            $data = $synchronizer->decryptData($request->get('token', ''));
            foreach (['app_id', 'username', 'buy_count', 'created_at', 'route'] as $key) {
                assert(isset($data[$key]));
            }

            assert($data['app_id'] === config('mm2.application_id'));
        } catch (\Exception $e) {
            return view('errors.token_expired');
        }

        $username = '@' . $data['username'];
        $buyCount = $data['buy_count'];
        $createdAt = Carbon::createFromTimestamp($data['created_at']);
        // $adminRole = isset($data['adm_role']) ? $data['adm_role'] : NULL;
        $roleTypeId = $data['role_type_id'] ?? Role::User;
        $route = $data['route'];

        /** @var User $user */
        if (($user = User::whereUsername($username)->first()) === null) {
            $user = User::create([
                'username' => $username,
                'password' => '',
                'role' => User::ROLE_CATALOG
            ]);

            event(new Registered($user));
        }

        // $user->admin_role_type = $adminRole;
        $user->role_type_id = $roleTypeId;
        $user->buy_count = $buyCount;
        $user->created_at = $createdAt;
        $user->last_login_at = Carbon::now();
        $user->save();

        \Auth::login($user);
        return redirect()->to($route);
    }
}