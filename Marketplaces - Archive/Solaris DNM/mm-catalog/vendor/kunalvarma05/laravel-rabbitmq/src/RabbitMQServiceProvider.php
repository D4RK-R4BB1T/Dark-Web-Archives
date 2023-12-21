<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // A single instance per request is enough
        $this->app->singleton(RabbitMQManager::class, function (Application $app) {
            return new RabbitMQManager($app);
        });

        // Create a substitute binding
        $this->app->bind('rabbitmq', function (Application $app) {
            return $app->make(RabbitMQManager::class);
        });

        $this->publishes([
            __DIR__ . '/../config/rabbitmq.php' => config_path('rabbitmq.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['rabbitmq', RabbitMQManager::class];
    }
}
