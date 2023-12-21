<?php

namespace App\Providers;

use App\Conversation;
use App\Policies\ConversationPolicy;
use App\Policies\ProductPolicy;
use App\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Conversation::class => ConversationPolicy::class,
        Product::class => ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();


        // Gate for moderator
        Gate::define('has-access', function($user, $permission){
            return $user -> hasPermission($permission);
        });

        /**
         * Admin grants access to all
         */
        Gate::before(function($user, $permission){
            if($user -> isAdmin()){
                return true;
            }
        });
    }
}
