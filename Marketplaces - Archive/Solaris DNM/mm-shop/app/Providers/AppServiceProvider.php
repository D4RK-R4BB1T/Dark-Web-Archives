<?php

namespace App\Providers;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.debug'))
        {
            $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
            $kernel->pushMiddleware('\Clockwork\Support\Laravel\ClockworkMiddleware');

            if(config('mm2.debug.enable_sql_query_log')) {
                DB::enableQueryLog();
            }
        }

        \Validator::extend('not_in_icase', function($attribute, $value, $parameters, $validator) {
            return !collect($parameters)
                ->map(function($value) { return mb_strtolower($value);})
                ->contains(mb_strtolower($value));
        });

        \Validator::extend('not_starts_with_letter', function($attribute, $value, $parameters, $validator) {
            return !starts_with_letter($value, collect($parameters));
        });

        \Validator::extend('not_starts_with_word', function($attribute, $value, $parameters, $validator) {
            $parameters = collect($parameters);
            foreach ($parameters as $word) {
                if (starts_with_word($value, $word)) {
                    return FALSE;
                }
            }
            return TRUE;
        });

        \Validator::extend('pgp_public_key', function($attribute, $value, $parameters, $validator) {
            return (bool) preg_match('/^-----BEGIN PGP PUBLIC KEY BLOCK-----(.*?)-----END PGP PUBLIC KEY BLOCK-----$/s', $value);
        });

        AbstractPaginator::currentPathResolver(function () {
            /** @var \Illuminate\Routing\UrlGenerator $url */
            $url = app('url');
            return $url->current();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require __DIR__ . '/../Packages/helpers.php';
    }
}
