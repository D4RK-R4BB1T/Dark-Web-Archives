<?php
/**
 * File: UsersController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\User;
use Illuminate\Http\Request;

class UsersController extends ManagementController
{
    public function note(Request $request, $userId)
    {
        $this->authorize('management-sections-orders');

        $user = User::findOrFail($userId);
        $user->note = $request->get('note');
        $user->save();

        return redirect()->back()->with('flash_success', 'Заметка обновлена');
    }
}