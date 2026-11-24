<?php

declare(strict_types=1);

namespace Modular\Modules\Home\Http\Controllers;

use Modular\Modules\Auditoria\Services\IAuditoriaService;
use Modular\Modules\Exemplo\Services\IExemploService;
use Modular\Shared\Http\Controllers\SharedController;
use Modular\Shared\Support\View;

class HomeController extends SharedController
{
    private int $limit = 5;
    public function __construct(
        private IAuditoriaService $auditoriaService,
        private IExemploService $exemploService
    ) {
    }
    public function index(): string
    {
        $content = View::render('Home/index', [
            'nome' => 'Ola, ' . $_SESSION['usuario'],
            'countExemplo' => $this->exemploService->count(),
            'lastRegistros' => $this->auditoriaService->getLastRegistros($this->limit),
        ]);

        return parent::getPage('PROJETO - Dashboard', $content, [
            'showSidebar' => true,
            'activePage' => 'dashboard',
        ]);
    }
}
