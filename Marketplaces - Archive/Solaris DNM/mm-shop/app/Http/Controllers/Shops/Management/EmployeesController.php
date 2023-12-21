<?php
/**
 * File: EmployeesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Employee;
use App\Http\Requests\ShopEmployeesAccessQuestsRequest;
use App\Http\Requests\ShopEmployeesAddRequest;
use App\Http\Requests\ShopEmployeesEditRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Thread;
use App\Shop;
use App\User;
use Illuminate\Http\Request;

class EmployeesController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-employees');
            return $next($request);
        });

        \View::share('page', 'employees');
    }

    public function index(Request $request)
    {
        \View::share('section', 'index');

        $employeesLog = $this->shop->employeesLog()
            ->applySearchFilters($request)
            ->with(['employee', 'employee.user', 'good', 'position', 'package', 'page'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->has('employee') ? 100 : 20);

        return view('shop.management.employees.index', [
            'employeesLog' => $employeesLog
        ]);
    }

    public function showAddForm(Request $request)
    {
        \View::share('section', 'add');
        if (($this->shop->getTotalAvailableEmployeesCount() - $this->shop->employees()->count()) <= 0) {
            return redirect('/shop/management/employees')->with('flash_warning', 'Превышен лимит активных сотрудников. Откройте вкладку "Система" для получения большей информации.');
        }
        return view('shop.management.employees.add');
    }

    public function add(ShopEmployeesAddRequest $request)
    {
        \View::share('section', 'add');
        $user = User::where('username', $request->get('username'))->firstOrFail();
        if (($this->shop->getTotalAvailableEmployeesCount() - $this->shop->employees()->count()) <= 0) {
            return redirect('/shop/management/employees')->with('flash_warning', 'Превышен лимит активных сотрудников. Откройте вкладку "Система" для получения большей информации.');
        }

        $thread = Thread::create([
            'subject' => 'Приглашение на работу',
        ]);

        $thread->addParticipant($user->id);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'body' => 'Магазин ' . $this->shop->title . ' предлагает вам стать сотрудником магазина.',
        ]);

        $invitation = \Crypt::encrypt([
            'shop_id' => $this->shop->id,
            'user_id' => $user->id,
            'thread_id' => $thread->id
        ]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'body' => 'Для принятия заявки нажмите кнопку "Принять приглашение".<br /><a href="'."{url}/messages/employee_invite?code=$invitation".'" class="btn btn-orange">Принять приглашение</a>',
            'system' => true
        ]);

        return redirect('/shop/management/employees/add')->with('flash_success', 'Приглашение отправлено.');
    }

    public function showAccessGoodsForm(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        return view('shop.management.employees.access.goods', [
            'employee' => $employee
        ]);
    }

    public function accessGoods(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        if ($request->get('goods_all')) {
            $employee->goods_create = true;
            $employee->goods_delete = true;
            $employee->goods_edit = true;
        } else {
            $employee->goods_create = $request->has('goods_create_delete');
            $employee->goods_delete = $request->has('goods_create_delete');
            $employee->goods_edit = $request->has('goods_edit');
        }

        $employee->goods_only_own_city = $request->has('goods_only_own_city');
        $employee->save();
        return redirect('/shop/management/employees/access/quests/' . $employeeId)->with('flash_success', 'Настройки сохранены.');
    }

    public function showAccessQuestsForm(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        $goods = $this->shop->goods()
            ->with(['cities'])
            ->get();

        return view('shop.management.employees.access.quests', [
            'employee' => $employee,
            'goods' => $goods
        ]);
    }

    public function accessQuests(ShopEmployeesAccessQuestsRequest $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        $employee->quests_create = $request->has('quests_create');
        $employee->quests_edit = $request->has('quests_edit');
        $employee->quests_delete = $request->has('quests_delete');
        $employee->quests_not_only_own = $request->has('quests_not_only_own');
        $employee->quests_only_own_city = $request->has('quests_only_own_city');
        $employee->quests_autojoin = $request->has('quests_autojoin');
        $employee->quests_preorders = $request->has('quests_preorders');
        $employee->quests_allowed_goods = $request->get('quests_allowed_goods', []);
        $employee->quests_moderate = $request->has('quests_moderate');
        $employee->save();

        return redirect('/shop/management/employees/access/sections/' . $employeeId)->with('flash_success', 'Настройки сохранены.');
    }

    public function showAccessSectionsForm(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        return view('shop.management.employees.access.sections', [
            'employee' => $employee
        ]);
    }

    public function accessSections(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        // права на финансы может выдать только владелец
        $employee->sections_finances = false;
        if(\Auth::user()->can('management-owner'))
            $employee->sections_finances = $request->has('sections_finances');

        $employee->sections_employees = $request->has('sections_employees');
        $employee->sections_orders = $request->has('sections_orders');
        $employee->sections_messages = $request->has('sections_messages');
        if ($employee->sections_messages) { // force enable orders access if messages is checked
            $employee->sections_orders = true;
        }
        if($employee->sections_orders) { // force enable own orders access on all orders access
            $employee->sections_own_orders = true;
        }
        $employee->sections_messages_private = $request->has('sections_messages_private');
        $employee->sections_messages_private_description = $request->get('sections_messages_private_description');
        $employee->sections_messages_private_autojoin = $request->has('sections_messages_private_autojoin');
        $employee->sections_settings = $request->has('sections_settings');
        $employee->sections_paid_services = $request->has('sections_paid_services');
        $employee->sections_pages = $request->has('sections_pages');
        $employee->sections_stats = $request->has('sections_stats');
        $employee->sections_qiwi = $request->has('sections_qiwi');
        $employee->sections_discounts = $request->has('sections_discounts');
        $employee->sections_moderate = $request->has('sections_moderate');

        $employee->save();

        return redirect('/shop/management/employees/' . $employeeId)->with('flash_success', 'Настройки сохранены.');
    }

    public function employee(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        return view('shop.management.employees.employee', [
            'employee' => $employee,
            'editing' => false
        ]);
    }

    public function showEmployeeEditForm(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        return view('shop.management.employees.employee', [
            'employee' => $employee,
            'editing' => true
        ]);
    }

    public function employeeEdit(ShopEmployeesEditRequest $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        $employee->update([
            'city_id' => $request->get('city'),
            'note' => $request->get('note')
        ]);

        return redirect('/shop/management/employees/' . $employeeId)->with('flash_success', 'Настройки сохранены.');
    }

    public function showEmployeeDeleteForm(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        if ($employee->role == Employee::ROLE_OWNER) {
            return abort(403);
        }

        return view('shop.management.employees.delete', [
            'employee' => $employee
        ]);
    }

    public function employeeDelete(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        if ($employee->role == Employee::ROLE_OWNER) {
            return abort(403);
        }

        $employee->delete();
        return redirect('/shop/management/employees')->with('flash_success', 'Сотрудник уволен.');
    }
}