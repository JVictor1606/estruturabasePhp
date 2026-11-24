<?php

declare(strict_types=1);

namespace Modular\Modules\Usuarios\Http\Controllers;

use Modular\Core\Logger;
use Modular\Modules\Usuarios\Domain\UsuarioException;
use Modular\Modules\Usuarios\Http\Requests\AtualizarSenhaFormRequest;
use Modular\Modules\Usuarios\Services\IUserService;
use Modular\Shared\Http\Controllers\SharedController;
use Modular\Shared\Support\Toast;
use Modular\Shared\Support\Url;
use Modular\Shared\Support\View;

class ConfigController extends SharedController
{
    private IUserService $userService;
    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): string
    {
        if ($_SESSION['usuario'] === null) {
            Url::redirect('/');
        }
        $toast = Toast::pull();

        $content = View::render('Config/index', [
            'usuario' => $this->GetUsuarioLogado(),
            'urlAtualizarSenha' => Url::path('/configuracoes/AtualizarSenha'),
        ]);

        return parent::getPage(
            'PROJETO - Configurações',
            $content,
            [
                'showSidebar' => true,
                'activePage' => 'configuracoes',
            ],
            $toast
        );
    }

    public function atualizarSenha(): void
    {
        try {
            $validated = (new AtualizarSenhaFormRequest($_POST))->redirectOnFail();
            $data = $validated->validated();

            $userId = (string) ($_SESSION['user_id'] ?? '');

            $this->userService->trocarSenha($userId, $data['senha_atual'], $data['nova_senha']);

            Toast::success('Senha atualizada com sucesso.');
            Url::redirect('/configuracoes');
        } catch (UsuarioException $th) {
            Toast::error($th->getMessage());
            Url::redirect('/configuracoes');
        } catch (\Exception $e) {
            Toast::error('Ocorreu um erro inesperado. Tente novamente.');
            Logger::error('Erro ao atualizar senha: ' . $e->getMessage());
            Url::redirect('/configuracoes');
        }
    }

    private function getUsuarioLogado()
    {
        return $this->userService->GetUserById($_SESSION['user_id']);
    }
}
