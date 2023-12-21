<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * App\EmployeesLog
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $employee_id
 * @property integer $item_id
 * @property string $action
 * @property string $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Good $good
 * @property-read \App\GoodsPackage $package
 * @property-read \App\GoodsPosition $position
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereItemId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereAction($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereData($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property integer $good_id
 * @property integer $package_id
 * @property integer $position_id
 * @property integer $order_id
 * @property integer $page_id
 * @property-read \App\Order $order
 * @property-read \App\Employee $employee
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog wherePackageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog wherePositionId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog whereOrderId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog wherePageId($value)
 * @property-read \App\Page $page
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog applySearchFilters($request)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesLog filterEmployee($employeeId)
 */
class EmployeesLog extends Model
{
    const ACTION_GOODS_ADD = 'goods_add';
    const ACTION_GOODS_EDIT = 'goods_edit';
    const ACTION_GOODS_DELETE = 'goods_delete';
    const ACTION_PACKAGES_ADD = 'packages_add';
    const ACTION_PACKAGES_EDIT = 'packages_edit';
    const ACTION_PACKAGES_DELETE = 'packages_delete';
    const ACTION_QUESTS_ADD = 'quests_add';
    const ACTION_QUESTS_EDIT = 'quests_edit';
    const ACTION_QUESTS_DELETE = 'quests_delete';
    const ACTION_ORDERS_PREORDER = 'orders_preorder';
    const ACTION_FINANCE_PAYOUT = 'finance_payout';
    const ACTION_SETTINGS_PAGE_ADD = 'settings_page_add';
    const ACTION_SETTINGS_PAGE_EDIT = 'settings_page_edit';
    const ACTION_SETTINGS_PAGE_DELETE = 'settings_page_delete';
    const ACTION_QUESTS_MODERATE_ACCEPT = 'quests_moderate_accept';
    const ACTION_QUESTS_MODERATE_DECLINE = 'quests_moderate_decline';

    protected $table = 'employees_logs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'employee_id', 'item_id', 'good_id', 'package_id', 'position_id',
        'order_id', 'page_id', 'action', 'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Good
     */
    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|GoodsPackage
     */
    public function package()
    {
        return $this->belongsTo('App\GoodsPackage', 'package_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|GoodsPosition
     */
    public function position()
    {
        return $this->belongsTo('App\GoodsPosition', 'position_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Order
     */
    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Employee
     */
    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Page
     */
    public function page()
    {
        return $this->belongsTo('App\Page', 'page_id', 'id');
    }

    public function getHumanAction()
    {
        switch ($this->action) {
            case EmployeesLog::ACTION_GOODS_ADD:
                return 'Добавлен товар';

            case EmployeesLog::ACTION_GOODS_EDIT;
                return 'Отредактирован товар';

            case EmployeesLog::ACTION_GOODS_DELETE:
                return 'Удален товар';

            case EmployeesLog::ACTION_PACKAGES_ADD:
                return 'Добавлена упаковка';

            case EmployeesLog::ACTION_PACKAGES_EDIT:
                return 'Отредактирована упаковка';

            case EmployeesLog::ACTION_PACKAGES_DELETE:
                return 'Удалена упаковка';

            case EmployeesLog::ACTION_QUESTS_ADD:
                return 'Добавлен квест';

            case EmployeesLog::ACTION_QUESTS_EDIT:
                return 'Отредактирован квест';

            case EmployeesLog::ACTION_QUESTS_DELETE:
                return 'Удален квест';

            case EmployeesLog::ACTION_ORDERS_PREORDER:
                return 'Выдан предзаказ';

            case EmployeesLog::ACTION_FINANCE_PAYOUT:
                return 'Совершена выплата';

            case EmployeesLog::ACTION_SETTINGS_PAGE_ADD:
                return 'Добавлена страница';

            case EmployeesLog::ACTION_SETTINGS_PAGE_EDIT:
                return 'Отредактирована страница';

            case EmployeesLog::ACTION_SETTINGS_PAGE_DELETE:
                return 'Удалена страница';

            case EmployeesLog::ACTION_QUESTS_MODERATE_ACCEPT:
                return 'Принят квест из модерации';

            case EmployeesLog::ACTION_QUESTS_MODERATE_DECLINE:
                return 'Удален квест из модерации';

            default:
                return 'Unknown action: ' . $this->action;
        }
    }

    public function scopeApplySearchFilters(\Illuminate\Database\Eloquent\Builder $employeesLog, Request $request)
    {
        if (!empty($employeeId = $request->get('employee'))) {
            $employeesLog = $employeesLog->filterEmployee($employeeId);
        }

        return $employeesLog;
    }

    public function scopeFilterEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Log user activity
     *
     * @param User $user
     * @param $action
     * @param array|null $belongings
     * @param array|null $data
     * @throws \Exception
     */
    public static function log(User $user, $action, array $belongings = [], array $data = null)
    {
        if (!$user->employee) {
            throw new \Exception('User does not belong to any shop.');
        }

        EmployeesLog::create(array_merge($belongings, [
            'shop_id' => $user->employee->shop_id,
            'employee_id' => $user->employee->id,
            'action' => $action,
            'data' => $data
        ]));
    }
}