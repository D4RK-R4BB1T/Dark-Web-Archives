<?php
/**
 * File: CaptchaPhraseBuilder.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


use Gregwar\Captcha\PhraseBuilderInterface;

class CaptchaPhraseBuilder implements PhraseBuilderInterface
{
    /**
     * Generates  random phrase of given length with given charset
     */
    public function build($length = 6, $charset = 'abcdefghijkmnpqrstuvwxyz23456789')
    {
        $phrase = '';
        $chars = str_split($charset);

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $chars[array_rand($chars)];
        }

        return $phrase;
    }

    /**
     * "Niceize" a code
     */
    public function niceize($str)
    {
        return strtolower($str);
    }
}
