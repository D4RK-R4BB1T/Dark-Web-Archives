<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\EmployeesEarning
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $employee_id
 * @property integer $order_id
 * @property float $amount
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Shop $shop
 * @property-read \App\Employee $employee
 * @property-read \App\Order $order
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereOrderId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesEarning whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmployeesEarning extends Model
{
    protected $table = 'employees_earnings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'employee_id', 'order_id', 'amount', 'description'
    ];

    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id', 'id');
    }
}
