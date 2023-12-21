<?php

namespace App\Http\Middleware;

use App\Providers\DynamicPropertiesProvider;
use App\Role;
use App\Shop;
use Closure;
use Illuminate\Support\Str;

class RedirectIfService
{
    private $serviceExcept = [
        'messages',
        'shop/service/messages/new',
        'shop/service/orders',
    ];

    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        // skip check for auth and messages
        if(Str::contains($request->path(), ['auth/', 'messages'])) {
            return $next($request);
        }

        $shop = Shop::getDefaultShop();
        $user = $request->user();
        $role = ($user && $user->role()) ? $user->role()->first()->id : Role::User;
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        // allow only security service & shop owner to view pages
        if(!is_null($propertiesProvider->getBool(DynamicPropertiesProvider::KEY_ENABLED)) &&
            (!$user || (!$user->isSecurityService() && !$user->isModerator() && !$user->can('management-owner')))) {
            return response()->view('shop.security-service-disabled', compact('shop'));
        }

        switch ($role) {
            case Role::SeniorModerator:
                $this->serviceExcept[] = 'shop/service/finances';
                break;
            case Role::SecurityService:
            case Role::Admin:
                $this->serviceExcept[] = 'shop/service/finances';
                $this->serviceExcept[] = 'shop/service/security/shop';
                $this->serviceExcept[] = 'shop/service/security/integrations';
                $this->serviceExcept[] = 'shop/service/security/plan';
                $this->serviceExcept[] = 'shop/service/security/users';
                break;
        }

        $serviceUrl = Str::contains($request->path(), 'shop/service/');

        // reject loading service url for anyone except security service and moderators
        if($serviceUrl && !$user->isSecurityService() && !$user->isModerator()) {
            abort(403);
        }

        // redirect security service or moderator in case of invalid path
        if($user && !Str::contains($request->path(), $this->serviceExcept) && ($user->isSecurityService() || $user->isModerator())) {
            return $user->isSecurityService() ? redirect('/shop/service/security/shop') : redirect('/shop/service/orders');
        }

        return $next($request);
    }
}
