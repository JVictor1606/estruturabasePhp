<?php

declare(strict_types=1);

namespace Modular\Shared\Http\Controllers;

use Modular\Shared\Support\Url;
use Modular\Shared\Support\View;

class ErrorController extends SharedController
{
    public static function notFound(): string
    {
        $content = View::render('Errors/404', [
            'baseUrl' => Url::path('/'),
        ]);

        return parent::getPage('404 - Página não encontrada', $content, [
            'showSidebar' => false,
            'bodyClass' => 'error-404',
        ]);
    }
}
