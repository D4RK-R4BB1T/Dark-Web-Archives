<?php

namespace App\Providers;

use App\Shop;
use App\ShopOverride;

class DynamicPropertiesProvider
{
    private $shop_id;
    private $overriden;

    const KEY_ENABLED = 'enabled';
    const KEY_INTEGRATION_CATALOG = 'integrations_catalog';
    const KEY_WDRAW_SHOP_WALLET = 'withdraw_shop_wallet';

    public function __construct()
    {
        $this->overriden = collect([]);
        $this->shop_id = Shop::getDefaultShop()->id;
    }

    public function register($shop_id)
    {
        if($shop_id) {
            $this->shop_id = $shop_id;
        }

        $this->overriden = ShopOverride::where('shop_id', '=', $this->shop_id)->get();
    }

    public function getBool($key)
    {
        switch ($key)
        {
            case self::KEY_ENABLED:
            case self::KEY_INTEGRATION_CATALOG:
            case self::KEY_WDRAW_SHOP_WALLET:
                return $this->getBoolean($key);
            default:
                throw new \Exception('Invalid key name.');
        }
    }

    /**
     * @throws \Exception
     */
    public function setBool($key, $value)
    {
        switch ($key)
        {
            case self::KEY_ENABLED:
            case self::KEY_INTEGRATION_CATALOG:
            case self::KEY_WDRAW_SHOP_WALLET:
                $this->setBoolean($key, $value);
                break;
            default:
                throw new \Exception('Invalid key name.');
        }
    }

    public function delete($key)
    {
        switch ($key)
        {
            case self::KEY_ENABLED:
            case self::KEY_INTEGRATION_CATALOG:
            case self::KEY_WDRAW_SHOP_WALLET:
                ShopOverride::where('shop_id', '=', $this->shop_id)->where('param', '=', $key)->delete();
                break;
            default:
                throw new \Exception('Invalid key name.');
        }
    }

    private function getBoolean($key)
    {
        $param = $this->overriden->filter(function ($over) use ($key) {
            return $over->param === $key;
        })->first();

        if($param) {
            return (bool) $param->value;
        }

        return $param;
    }

    private function setBoolean($key, $value)
    {
        ShopOverride::updateOrCreate(['shop_id' => $this->shop_id, 'param' => $key], ['value' => (bool) $value]);
    }
}