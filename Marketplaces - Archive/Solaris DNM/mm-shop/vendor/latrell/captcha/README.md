For Laravel 4, please use the [1.1 branch](https://github.com/Latrell/Captcha/releases/tag/1.1)!

# Captcha for Laravel 5

A simple [Laravel 5](http://laravel.com/) service provider for including the [Captcha for Laravel 5](https://github.com/Gregwar/Captcha).

This library is not maintained for 3rd party use.

## Preview

![Captchas examples](http://gregwar.com/captchas.png)

## Installation

```
composer require latrell/captcha dev-master
```

## Usage

To use the Captcha Service Provider, you must register the provider when bootstrapping your Laravel application. There are
essentially two ways to do this.

Find the `providers` key in `config/app.php` and register the Captcha Service Provider.

```php
    'providers' => [
        // ...
        'Latrell\Captcha\CaptchaServiceProvider',
    ]
```

Find the `aliases` key in `config/app.php`.

```php
    'aliases' => [
        // ...
        'Captcha' => 'Latrell\Captcha\Facades\Captcha',
    ]
```

Custom error messages.
Add key `captcha` to `resources/lang/[local]/validation.php`

```php
return [
	// ...
	'captcha' => '图片验证码不正确。',
];
```

Then publish the config file with `php artisan vendor:publish`. This will add the file `config/latrell-captcha.php`.
This config file is the primary way you interact with Captcha.

## Example Usage

```php

    // [your site path]/app/Http/routes.php

    Route::any('/captcha-test', function()
    {

        if (Request::getMethod() == 'POST')
        {
            $rules =  ['captcha' => 'required|captcha'];
            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails())
            {
                echo '<p style="color: #ff0000;">Incorrect!</p>';
            }
            else
            {
                echo '<p style="color: #00ff30;">Matched :)</p>';
            }
        }

        $content = Form::open(array(URL::to(Request::segment(1))));
        $content .= '<p>' . HTML::image(Captcha::url()) . '</p>';
        $content .= '<p>' . Form::text('captcha') . '</p>';
        $content .= '<p>' . Form::submit('Check') . '</p>';
        $content .= '<p>' . Form::close() . '</p>';
        return $content;

    });
```

## Links

* [L5 Captcha on Github](https://github.com/latrell/captcha)
* [L5 Captcha on Packagist](https://packagist.org/packages/Latrell/captcha)
* [Captcha for Gregwar](https://github.com/Gregwar/Captcha)
* [License](http://www.opensource.org/licenses/mit-license.php)
* [Laravel website](http://laravel.com)
* [MeWebStudio website](http://latrell.me)
