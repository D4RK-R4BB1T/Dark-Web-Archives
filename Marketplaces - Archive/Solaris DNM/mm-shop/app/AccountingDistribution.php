<?php

namespace App;

use App\Packages\Utils\Formatters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * App\AccountingDistribution
 *
 * @property int $id
 * @property int $lot_id
 * @property int $employee_id
 * @property float $amount
 * @property float $available_amount
 * @property float $proceed_btc
 * @property string $note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereAvailableAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereLotId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereNote($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereProceedBtc($value)
 * @method static \Illuminate\Database\Query\Builder|\App\AccountingDistribution whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Employee $employee
 * @property-read \App\AccountingLot $lot
 */
class AccountingDistribution extends Model
{
    protected $table = 'accounting_distributions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'lot_id', 'employee_id', 'amount', 'available_amount', 'proceed_btc', 'note'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|AccountingLot
     */
    public function lot()
    {
        return $this->belongsTo('App\AccountingLot', 'lot_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Builder|Employee
     */
    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    /**
     * @return float
     */
    public function getTotalWeight()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getHumanTotalWeight()
    {
        return Formatters::getHumanWeight($this->getTotalWeight(), $this->lot->measure);
    }

    /**
     * @return float
     */
    public function getAvailableWeight()
    {
        return $this->available_amount;
    }

    /**
     * @return string
     */
    public function getHumanAvailableWeight()
    {
        return Formatters::getHumanWeight($this->getAvailableWeight(), $this->lot->measure);
    }
}
