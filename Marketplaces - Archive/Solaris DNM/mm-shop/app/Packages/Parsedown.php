<?php
/**
 * File: Parsedown.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


class Parsedown extends \Parsedown
{
    protected function inlineLink($Excerpt)
    {
        $a = parent::inlineLink($Excerpt);
        if ($a) {
            $a['element']['attributes']['target'] = '_blank';
            return $a;
        }
    }

    protected function inlineUrl($Excerpt)
    {
        $a = parent::inlineUrl($Excerpt);
        if ($a) {
            $a['element']['attributes']['target'] = '_blank';
            return $a;
        }
    }

    protected function inlineUrlTag($Excerpt)
    {
        $a = parent::inlineUrlTag($Excerpt);
        if ($a) {
            $a['element']['attributes']['target'] = '_blank';
            return $a;
        }

    }

    protected function blockTable($Line, array $Block = null)
    {
        $table = parent::blockTable($Line, $Block);
        if ($table) {
            $table['element']['attributes']['class'] = 'table table-header table-bordered markdown-content';
            return $table;
        }
    }

    protected function inlineImage($Excerpt)
    {
        $image = parent::inlineImage($Excerpt);
        if ($image) {
            $image['element']['attributes']['style'] = 'max-width: 100%; max-height: 1000px;';
            return $image;
        }
    }

    protected function blockRule($Line)
    {
        $hr = parent::blockRule($Line);
        if ($hr) {
            $hr['element']['attributes']['class'] = 'small';
            return $hr;
        }
    }
}