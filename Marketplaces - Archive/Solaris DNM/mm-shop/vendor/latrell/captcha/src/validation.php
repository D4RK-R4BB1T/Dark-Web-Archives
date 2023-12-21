<?php

Validator::extend(Config::get('latrell-captcha.validator_name'), function($attribute, $value, $parameters)
{
    return Captcha::check($value);
});
