<?php
namespace Latrell\Captcha;

use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../../config/config.php' => config_path('latrell-captcha.php')
		], 'config');

		require __DIR__ . '/../../routes.php';
		require __DIR__ . '/../../validation.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'latrell-captcha');

		$this->app->singleton('captcha', function ($app) {
			return Captcha::instance();
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'captcha'
		);
	}
}
