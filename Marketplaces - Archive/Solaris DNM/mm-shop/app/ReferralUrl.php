<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\ReferralUrl
 *
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property float $fee
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereFee($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\ReferralUrl whereUserId($value)
 * @mixin \Eloquent
 */
class ReferralUrl extends Model {
    protected $table = 'referral_urls';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id', 'slug', 'fee'
    ];
}