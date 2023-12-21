<?php

namespace App\Traits;

use App\Address;
use App\Exceptions\RedirectException;
use App\Exceptions\RequestException;
use App\User;
use Carbon\Carbon;
use App\Vendor as VendorModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Vendorable
{
    /**
     * Returns true if the user is vendor
     *
     * @return bool
     */
    public function isVendor()
    {
        return VendorModel::where('id', $this -> getId()) -> exists();
    }

    /**
     * Return Vendor instance of the user
     *
     * @return \App\Vendor
     */
    public function vendor()
    {
        return $this -> hasOne(VendorModel::class, 'id', 'id');
    }

    /**
     * Returns true if the user paid to the one of the deposit addresses
     *
     * @return bool
     */
    private function depositedEngouh()
    {
        foreach ($this -> vendorPurchases as $depositAddress){
            if($depositAddress -> isEnough()){
                return true;
            }
        }
        return false;
    }

    /**
     * Creates instance of the Vendor from user
     *
     * @throws RequestException
     */
    public function becomeVendor($address = null)
    {
        if(!$this -> hasPGP())
            throw new RequestException('You can\'t become vendor if you don\'t have PGP key!');

        // Vendor must have addresses of each coin
//        foreach (array_keys(config('coins.coin_list')) as $coinName){
//            // if the coin doesnt exists
//            if(!$this -> addresses() -> where('coin', $coinName) -> exists())
//                throw new RedirectException("You need to have '" . strtoupper($coinName) . "' address in your account to become vendor!", route('profile.index'));
//        }
        // check if the user deposited addres
        throw_unless($this -> depositedEngouh(), new RequestException("You must deposit enough funds to the one address!"));

        try{
            DB::beginTransaction();

            // update balances of the vendor purchases
            foreach ($this -> vendorPurchases as $depositAddress){
                $depositAddress->amount = $depositAddress->getBalance();

                // Unload funds to market address
                if($depositAddress->getBalance()>0)
                    $depositAddress->unloadFunds();

                $depositAddress->save();
            }


            VendorModel::insert([
                'id' => $this -> getId(),
                'vendor_level' => 0,
                'about' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new RequestException("Error happened! Try again later!");
        }
    }
    
    
    /**
     * Methods required to display vendor statistics on profile
     */
    
    public function vendorSince(){
        return date_format($this->created_at,"M/Y");
    }


    public function completedOrders(){
        return $this->sales()->where('state','delivered')->count();
    }

    public function disputesLastYear($won = true,$months =12){
        $vendorID = $this->getId();
        return $this->sales()->whereHas('dispute',function ($query) use ($vendorID,$won,$months){
            $operator = '=';
            if (!$won){
                $operator = '!=';
            }
            $query->where('winner_id',$operator,$vendorID)->where("created_at",">", Carbon::now()->subMonths($months));
        })->count();
    }



}


