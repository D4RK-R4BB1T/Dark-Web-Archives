<?php
/**
 * File: MarkdownServiceProvider.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Providers;


use Indal\Markdown\Parser;

class MarkdownServiceProvider extends \Indal\Markdown\MarkdownServiceProvider
{
    public function register()
    {
        $this->app->singleton(Parser::class, function ($app) {
            $parsedown = new \App\Packages\Parsedown();

            $parsedown->setUrlsLinked(config('markdown.urls'));
            $parsedown->setMarkupEscaped(config('markdown.escape_markup'));
            $parsedown->setBreaksEnabled(config('markdown.breaks'));
            $parsedown->setSafeMode(true);

            return new Parser($parsedown);
        });

        $this->app->bind('markdown', Parser::class);
    }
}