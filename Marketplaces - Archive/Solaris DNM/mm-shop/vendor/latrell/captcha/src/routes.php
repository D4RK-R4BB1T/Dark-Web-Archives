<?php
Route::get(Config::get('latrell-captcha.route_name'), [
	'middleware' => Config::get('latrell-captcha.middleware'),
	'uses' => 'Latrell\Captcha\CaptchaController@getIndex'
]);
