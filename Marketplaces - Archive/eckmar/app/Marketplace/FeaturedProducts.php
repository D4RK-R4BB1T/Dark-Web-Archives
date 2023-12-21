<?php


namespace App\Marketplace;


use App\Marketplace\ModuleManager;

class FeaturedProducts
{

    protected static $moduleName = 'FeaturedProducts';

    public static function isEnabled(): bool {
        return ModuleManager::isEnabled(self::$moduleName);
    }

    public static function get(){
        try{
            $featuredStatus = resolve('FeaturedProductsModule\Status');
            $featuredProducts = $featuredStatus->getFeaturedProducts();
        } catch(\Exception $e){
            $featuredProducts = null;
        }

        return $featuredProducts;
    }
}