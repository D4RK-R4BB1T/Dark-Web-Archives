<?php

namespace App;

use App\Marketplace\Payment\FinalizeEarlyPayment;
use App\Traits\Experience;
use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Vendor extends User
{
    use Uuids;
    use Experience;
    protected $table = 'vendors';
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'vendor_level', 'about', 'created_at', 'updated_at'];

    /**
     * @return Collection of \App\User instaces of all admins
     */
    public static function allUsers()
    {
        $vendorIDs = Vendor::all() -> pluck('id');

        return User::whereIn('id', $vendorIDs) -> get();
    }


    /**
     * Return user instance of the vendor
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this -> hasOne(\App\User::class, 'id', 'id');
    }

    /**
     * Returns collection of vendors sales
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this -> hasMany(\App\Purchase::class, 'vendor_id', 'id') -> orderByDesc('created_at');
    }

    /**
     * Unread sales
     *
     * @return int
     */
    public function unreadSales()
    {
        return $this -> sales() -> where('read', false) -> count();
    }


    /**
     * Returns number of the sales which has particular state or number of all sales
     *
     * @param string $state
     * @return int
     */
    public function salesCount($state = '')
    {
        // If state doesnt exist
        if(!array_key_exists($state, Purchase::$states))
            return $this -> sales() -> count();

        return $this -> sales() -> where('state', $state) -> count();
    }


    /**
     * Relationship one to many with feedback
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feedback()
    {
        return $this -> hasMany(\App\Feedback::class, 'vendor_id', 'id');
    }

    /**
     * @return mixed
     */
    public function hasFeedback()
    {
        return $this -> feedback -> isNotEmpty();
    }

    /**
     * Count number of feedback rates left on this vendor
     *
     * @return int
     */
    public function countFeedback()
    {
        return $this -> feedback() -> count();
    }

    /**
     * Returns all vendor's feedback by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeedbackByType(string $type){
        return $this->feedback()->where('type',$type)->get();
    }

    /**
     * Count vendor's feedback by type
     *
     * @param string $type
     * @param int $months
     * @return int
     */
    public function countFeedbackByType(string $type,int $months = null){
        $query = $this->feedback()->where('type',$type);
        if ($months != null){
            $now = Carbon::now();
            $start = $now->subMonths($months);
            $query->where('created_at','>',$start);
        }
        return $query->count();
    }

    /**
     * Return string number with two decimals of average rate
     *
     * @param $type = [ 'quality_rate' | 'communication_rate' | 'shipping_rate' ]
     * @return string
     */
    public function avgRate($type)
    {
        if(!$this -> hasFeedback())
            return '0.00';

        if(!in_array($type, Feedback::$rates))
            $type = 'quality_rate';

        if($this -> feedback -> isEmpty())
            return '0.00';

        return number_format($this -> feedback -> avg($type), 2);

    }

    /**
     * Checks if vendor is trusted (set in admin panel)
     *
     * @return bool
     */
    public function isTrusted(): bool{
        if ($this->trusted){
            return true;
        }
        $lvl = $this->getLevel();
        $positive = $this->countFeedbackByType('positive');
        $neutral = $this->countFeedbackByType('neutral');
        $negative = $this->countFeedbackByType('negative');
        $total = $positive+$negative+$neutral;
        if($total == 0 || $lvl == 1 || $positive == 0){
            return false;
        }
        $percentage = round(($positive / $total) * 100);
        if ($lvl >= config('marketplace.trusted_vendor.min_lvl') &&
        $total >= config('marketplace.trusted_vendor.min_feedbacks') &&
        $percentage >= config('marketplace.trusted_vendor.percentage_of_feedback_positive')
        ){
            return true;
        }

        return false;

    }

    /**
     * Checks if vendor should have DWC tag
     *
     * @return bool
     */
    public function isDwc(): bool{
        if ($this->countFeedbackByType('negative') > config('marketplace.vendor_dwc_tag_count')){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns rounded avg rate of the feedback, half rounded on down
     * 4.1 => 4
     * 4.67 => 4.5
     *
     * @param $type
     * @return float
     */
    public function roundAvgRate($type)
    {
        $avgRateNumeric = (float)$this -> avgRate($type);
        return floor($avgRateNumeric * 2) / 2;
    }

    /**
     * If there is profile bg, return it, if not set random bg
     */
    public function getProfileBg(){
        if ($this->profilebg == null){
            $this->profilebg = array_random(config('vendor.profile_bgs'));
            $this->save();
        }

        return $this->profilebg;
    }

    /**
     * Vendors with most sales all time
     * @return mixed
     */
    public static function topVendors(){
        $vendors = Cache::remember('top_vendors_frontpage',config('marketplace.front_page_cache.top_vendors'),function(){
            return self::with('sales')
                ->join('purchases', 'purchases.vendor_id', '=', 'vendors.id')
                ->select('vendors.*', DB::raw('COUNT(purchases.id) as purchases_count')) // Avoid selecting everything from the stocks table
                ->orderBy('purchases_count', 'DESC')
                ->groupBy('vendors.id')
                ->limit(5)
                ->get();
        });
        return $vendors;
    }
    /**
     * Vendors with most sales in last 7 days
     * @return mixed
     */
    public static function risingVendors(){

        $vendors = Cache::remember('rising_vendors_frontpage',config('marketplace.front_page_cache.rising_vendors'),function(){
            return self::with('sales')
                ->join('purchases', 'purchases.vendor_id', '=', 'vendors.id')
                ->select('vendors.*', DB::raw('COUNT(purchases.id) as purchases_count')) // Avoid selecting everything from the stocks table
                ->orderBy('purchases_count', 'DESC')
                ->groupBy('vendors.id')
                ->where('vendors.created_at','>=',Carbon::now()->subDays(7))
                ->limit(5)
                ->get();
        });
        return $vendors;
        
    }
    
    public function getId(){
        return $this->id;
    }

    /**
     * Can Vendor use FE
     *
     * @return bool
     */
    public function canUseFe(){
        return $this->can_use_fe == 1 && FinalizeEarlyPayment::isEnabled();
    }

    /**
     * Check if vendor can use specific product type
     *
     * @param string $type
     *
     * @return bool
     */
    public function canUseType(string $type){
        if ($type == 'fe'){
            return $this->canUseFe();
        }
        return true;
    }
    


}
