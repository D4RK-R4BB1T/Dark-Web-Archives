<?php

namespace App\Providers;

use App\Marketplace\ModuleManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap Blade Engine services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::if('error', function ($name,$errors) {
            return $errors->has($name);
        });

        // if we are on  this route custom blade directive
        Blade::if('isroute', function($routeName){
            return strpos(\Route::currentRouteName(), $routeName) !== false;
        });

        Blade::if('vendor', function () {
            return auth() -> check() && auth() -> user() -> isVendor();
        });
        // check if the logged user is admin
        Blade::if('admin', function () {
            return auth() -> check() && auth() -> user() -> isAdmin();
        });

        Blade::if('moderator', function(){
           return auth() -> check() && auth() -> user() -> hasPermissions() && !auth() -> user() -> isAdmin();
        });

        Blade::if('hasAccess', function($permission){
            return auth() -> check() && (auth() -> user() -> isAdmin() || auth() -> user() -> hasPermission($permission));
        });

        Blade::if('isModuleEnabled', function($moduleName){
            return ModuleManager::isEnabled($moduleName);
        });

        Blade::if('search',function(){

            $display = false;
            $routes = [
                'home',
                'category'
            ];
            foreach($routes as $route){

                if (strpos(\Route::currentRouteName(), $route) !== false){

                    $display = true;
                    break;
                }
            }
            return $display;


        });

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
