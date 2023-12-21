<?php

namespace App;

use App\MessengerModels\Traits\Messageable;
use App\Packages\EncryptableTrait;
use App\Traits\Walletable;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * App\User
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $role
 * @property boolean $active
 * @property string $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsReview[] $goodsReviews
 * @property-read \App\Employee $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Message[] $messages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Participant[] $participants
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Thread[] $threads
 * @method static \Illuminate\Database\Query\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereRole($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $totp_key
 * @method static \Illuminate\Database\Query\Builder|\App\User whereTotpKey($value)
 * @property string $contacts_other
 * @property string $contacts_telegram
 * @property string $contacts_jabber
 * @method static \Illuminate\Database\Query\Builder|\App\User whereContactsOther($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereContactsTelegram($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereContactsJabber($value)
 * @property integer $buy_count
 * @method static \Illuminate\Database\Query\Builder|\App\User whereBuyCount($value)
 * @property string $last_login_at
 * @method static \Illuminate\Database\Query\Builder|\App\User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User applySearchFilters(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Database\Query\Builder|\App\User filterUsername($username)
 * @method static \Illuminate\Database\Query\Builder|\App\User orderByColumn($column)
 * @property string $note
 * @method static \Illuminate\Database\Query\Builder|\App\User whereNote($value)
 * @property string $pgp_key
 * @method static \Illuminate\Database\Query\Builder|\App\User wherePgpKey($value)
 * @property string $tg_token
 * @method static \Illuminate\Database\Query\Builder|\App\User whereTgToken($value)
 * @property-read \App\QiwiExchange $qiwiExchange
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\QiwiExchangeRequest[] $qiwiExchangeRequests
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ReferralUrl[] $referralUrls
 * @property int $referrer_id
 * @property float $referral_fee
 * @method static \Illuminate\Database\Query\Builder|\App\User whereReferralFee($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User whereReferrerId($value)
 * @property-read \App\User $referrer
 * @property int $group_id
 * @property-read \App\UserGroup $group
 * @method static \Illuminate\Database\Query\Builder|\App\User whereGroupId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User filterGroupId($groupId)
 * @property string $admin_role_type
 * @method static \Illuminate\Database\Query\Builder|\App\User whereAdminRoleType($value)
 * @property string $notification_last_read_at
 * @method static \Illuminate\Database\Query\Builder|\App\User whereNotificationLastReadAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Wallet[] $wallets
 * @property int $role_type_id
 */
class User extends Authenticatable
{
    use Messageable, Walletable, EncryptableTrait;

    const ROLE_SHOP = 'shop';
    const ROLE_SHOP_PENDING = 'shop_pending';
    const ROLE_USER = 'user';
    const ROLE_CATALOG = 'catalog';
    const ROLE_TELEGRAM = 'telegram';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'role_type_id', 'role', 'admin_role_type', 'referrer_id', 'referral_fee', 'group_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'wallet_key', 'contacts_other', 'contacts_telegram', 'contacts_jabber'
    ];

    protected $casts = [
        'last_login_at' => 'datetime'
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encryptable = [
        'contacts_other', 'contacts_jabber'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Order[]
     */
    public function orders()
    {
        return $this->hasMany('App\Order', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|GoodsReview[]
     */
    public function goodsReviews()
    {
        return $this->hasMany('App\GoodsReview', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|Employee
     */
    public function employee()
    {
        return $this->hasOne('App\Employee', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|QiwiExchange
     */
    public function qiwiExchange()
    {
        return $this->hasOne('App\QiwiExchange', 'user_id', 'id');
    }

    /**
     * @return int
     */
    public function activeOrdersCount()
    {
        return $this->orders()->where('status', '!=', Order::STATUS_FINISHED)->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Builder|Wallet
     */
    public function primaryWallet()
    {
        return $this->hasOne('App\Wallet', 'user_id', 'id')->where('type', Wallet::TYPE_PRIMARY)->first();
    }

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\Wallet', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|QiwiExchangeRequest[]
     */
    public function qiwiExchangeRequests()
    {
        return $this->hasMany('App\QiwiExchangeRequest', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|ReferralUrl[]
     */
    public function referralUrls()
    {
        return $this->hasMany('App\ReferralUrl', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|User
     */
    public function referrer()
    {
        return $this->hasOne('App\User', 'id', 'referrer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|UserGroup
     */
    public function group()
    {
        return $this->belongsTo('App\UserGroup', 'group_id', 'id');
    }

    public function externalExchanges()
    {
        return $this->hasMany('App\ExternalExchange', 'user_id', 'id');
    }

    public function scopeApplySearchFilters(\Illuminate\Database\Eloquent\Builder $users, Request $request)
    {
        if (!empty($username = $request->get('username'))) {
            $users = $users->filterUsername($username);
        }

        if (!empty($groupId = $request->get('group'))) {
            $users = $users->filterGroupId($groupId);
        }

        if (!empty($column = $request->get('order'))) {
            $users = $users->orderByColumn($column);
        }

        return $users;
    }

    public function scopeFilterUsername($query, $username)
    {
        return $query->where('username', 'LIKE', '%' . $username . '%');
    }

    public function scopeFilterGroupId($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeOrderByColumn($query, $column)
    {
        if (in_array($column, ['last_login_at', 'buy_count'])) {
            $query = $query->orderBy($column, 'desc');
        }
        return $query;
    }

    /**
     * @throws \Exception
     * @return Shop
     */
    public function shop()
    {
        if (!$this->employee) {
            throw new \Exception('User does not belong to any shop.');
        }

        return $this->employee->shop;
    }

    /**
     * @return bool
     */
    public function isSecurityService(): bool
    {
        $allowedTypes = [Role::SecurityService, Role::Admin];
        return $this->role === User::ROLE_CATALOG && in_array($this->role_type_id, $allowedTypes);
    }

    /**
     * @return bool
     */
    public function isModerator(): bool
    {
        $allowedTypes = [Role::JuniorModerator, Role::SeniorModerator];
        return $this->role === User::ROLE_CATALOG && in_array($this->role_type_id, $allowedTypes);
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        if(!$this->isSecurityService() && !$this->isModerator() && !$this->employee && $this->role !== self::ROLE_SHOP) {
            return e($this->username);
        }

        if($this->role === self::ROLE_SHOP) {
            return config('mm2.application_title');
        }

        if($this->employee) {
            return e($this->username);
        }

        return Cache::store('redis')->remember("public-name-$this->id", 3600, function () {
            switch ($this->role_type_id) {
                case Role::JuniorModerator:
                    return $this->username.' '.Role::JunModerRoleName;
                case Role::SeniorModerator:
                    return $this->username.' '.Role::SenModerRoleName;
                case Role::Admin:
                    return $this->username.' '.Role::AdminRoleName;
                case Role::SecurityService:
                    return Role::SecurityServiceRoleName;
            }

            return e('alien');
        });
    }

    public function getPrivateName()
    {
        return $this->role !== self::ROLE_SHOP ? $this->username : config('mm2.application_title');
    }

    /**
     * @return string
     */
    public function getPublicDecoratedName(): string
    {
        $publicName = $this->getPublicName();

        if(!$this->isSecurityService() && !$this->isModerator() && !$this->employee) {
            return $publicName;
        }

        if($this->employee) {
            return "<b class=\"text-info\">".$publicName."</b>";
        }

        return Cache::store('redis')->remember("public-decorated-name-$this->id", 3600, function() use($publicName) {
            if($this->role_type_id === Role::User) {
                return $publicName;
            }

            $style = Role::style($this->role_type_id);

            return starts_with($style, '#') ?
                        "<b style=\"color: $style\">$publicName</b>" :
                        "<b class=\"$style\">$publicName</b>";
        });
    }

    /**
     * @param $role_type_id
     * @return string
     */
    public static function getHumanRoleType($role_type_id): string
    {
        switch ($role_type_id) {
            case Role::JuniorModerator:
                return Role::JunModerRoleName;
            case Role::SeniorModerator:
                return Role::SenModerRoleName;
            case Role::Admin:
                return Role::AdminRoleName;
            case Role::SecurityService:
                return Role::SecurityServiceRoleName;
        }

        return "";
    }

    /**
     * @return int
     */
    public function getRating()
    {
        // TODO
        return 0;
    }

    /**
     * @return Carbon
     */
    public function getLastLogin()
    {
        return $this->last_login_at;
    }

    public function avatar()
    {
        if (in_array($this->role_type_id, [
            Role::JuniorModerator,
            Role::SeniorModerator,
            Role::Admin,
            Role::SecurityService])) {
            return '/assets/img/logo.svg';
        }

        return noavatar();
    }

    public function shouldShowGroupDiscount()
    {
        return $this->group;
    }

    private $_suggestedGroup = null;
    /**
     * @return UserGroup|\Illuminate\Database\Eloquent\Model|Builder|null
     */
    public function suggestDiscountGroup()
    {
        if (!is_null($this->_suggestedGroup)) {
            return $this->_suggestedGroup;
        }

        if ($this->group && $this->group->mode == UserGroup::MODE_MANUAL) {
            return NULL;
        }

        $newGroup = UserGroup::whereMode(UserGroup::MODE_AUTO)
            ->where('buy_count', '<=', $this->buy_count)
            ->orderBy('buy_count', 'desc')
            ->first();

        if ($newGroup && $newGroup->id == $this->group_id) {
            return NULL;
        }

        $this->_suggestedGroup = $newGroup;
        return $newGroup;
    }

    public function unreadNotifications()
    {
        if ($this->role == User::ROLE_CATALOG) { // should be notified through catalog
            return collect([]);
        }
        $query = Notification::where(function ($query) {
            return $query->whereNull('actual_until')->orWhere('actual_until', '>=', Carbon::now());
        });
        if ($this->notification_last_read_at) {
            $query = $query->where('created_at', '>=', $this->notification_last_read_at);
        }
        return $query->get();
    }

    public function isBetaUser()
    {
        // Username: betatester
        // Password: b1dc0ec89ba14262162edf20141f9220
        return $this->username === '@betatester';
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, "role_type_id", "id");
    }
}