<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\EmployeesPayout
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $employee_id
 * @property integer $sender_employee_id
 * @property integer $operation_id
 * @property string $description
 * @property-read \App\Shop $shop
 * @property-read \App\Employee $employee
 * @property-read \App\Employee $senderEmployee
 * @property-read \App\Operation $operation
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereShopId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereSenderEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereOperationId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\EmployeesPayout whereDescription($value)
 * @mixin \Eloquent
 */
class EmployeesPayout extends Model
{
    protected $table = 'employees_payouts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'shop_id', 'employee_id', 'sender_employee_id', 'operation_id', 'description'
    ];

    public $timestamps = false;

    public function shop()
    {
        return $this->belongsTo('App\Shop', 'shop_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    public function senderEmployee()
    {
        return $this->belongsTo('App\Employee', 'sender_employee_id', 'id');
    }

    public function operation()
    {
        return $this->belongsTo('App\Operation', 'operation_id', 'id');
    }
}
