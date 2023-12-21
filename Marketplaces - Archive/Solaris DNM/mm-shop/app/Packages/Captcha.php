<?php
/**
 * File: Captcha.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


use Config;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Str;
use Response;
use URL;

class Captcha extends \Latrell\Captcha\Captcha
{
    public static function url()
    {
        $uniqid = uniqid(gethostname(), true);
        $md5 = substr(md5($uniqid), 12, 8); // 8位md5
        $uint = hexdec($md5);
        $uniqid = sprintf('%010u', $uint);
        return URL::to(config('latrell-captcha.route_name') . '?' . $uniqid);
    }

    public function __construct()
    {
        $this->builder = new CaptchaBuilder(null, new CaptchaPhraseBuilder());

        $configKey = 'latrell-captcha.';

        $this->against_ocr = Config::get($configKey . 'against_ocr');
        $this->width = Config::get($configKey . 'width');
        $this->height = Config::get($configKey . 'height');
        $this->font = Config::get($configKey . 'font');
        $this->quality = Config::get($configKey . 'quality');

        $background_color = Config::get($configKey . 'background_color');
        if (is_string($background_color) && strlen($background_color) == 7 && $background_color{0} == '#') {
            $r = hexdec($background_color{1} . $background_color{2});
            $g = hexdec($background_color{3} . $background_color{4});
            $b = hexdec($background_color{5} . $background_color{6});
            $this->builder->setBackgroundColor($r, $g, $b);
        } elseif (is_array($background_color) && count($background_color) == 3) {
            $this->builder->setBackgroundColor($background_color[0], $background_color[1], $background_color[2]);
        }

        $this->builder->setDistortion(Config::get($configKey . 'distortion'));
        $this->builder->setBackgroundImages(Config::get($configKey . 'background_images'));
        $this->builder->setInterpolation(Config::get($configKey . 'interpolate'));
        $this->builder->setIgnoreAllEffects(Config::get($configKey . 'ignore_all_effects'));
    }
}