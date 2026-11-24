<?php

declare(strict_types=1);

namespace Src\App\Services;

use Src\Core\Auth;
use Src\App\Utils\EmailTemplates\RecuperacaoSenhaEmailTemplate;
use Src\App\Infrastructure\IEmailSender;
use Src\App\Infrastructure\IRepositories\IUserRepository;
use Src\App\Services\IServices\IRecuperacaoSenhaService;

class RecuperacaoSenhaService implements IRecuperacaoSenhaService
{
    private const EXPIRACAO_MINUTOS = 5;

    /** O código tem 6 dígitos (900 mil possibilidades): sem limite de erros ele pode ser adivinhado. */
    private const MAX_ERROS_POR_CODIGO = 5;

    public function __construct(
        private IUserRepository $userRepository,
        private IEmailSender $sender
    ) {
    }

    /**
     * Não revela se o e-mail está cadastrado: para e-mail desconhecido nada é enviado, mas a sessão
     * fica igual à de um envio real (com um código que ninguém conhece), então a tela seguinte e a
     * resposta a qualquer palpite são idênticas.
     */
    public function solicitarCodigoRecuperacao(string $email): void
    {
        $codigoExpirado = Auth::codigoExpirado();
        $emailDiferente = Auth::getEmailRecuperacao() !== $email;

        if (! $codigoExpirado && ! $emailDiferente) {
            return;
        }

        if ($this->userRepository->getUserByEmail($email) === null) {
            $this->registrarCodigoIsca($email);
            return;
        }

        $this->enviarCodigoRecuperacao($email);
    }

    public function enviarCodigoRecuperacao(string $email): void
    {
        $codigo = (string) random_int(100000, 999999);

        $corpo = RecuperacaoSenhaEmailTemplate::render($codigo, (string) self::EXPIRACAO_MINUTOS);
        $this->sender->send($email, 'Projeto - Código de Recuperação de Senha', $corpo);

        $this->guardarCodigo($email, $codigo);
    }

    public function verificarCodigo(string $email, string $codigo): bool
    {
        if (! isset($_SESSION['cod_senha_hash'], $_SESSION['codigo_expira_em'], $_SESSION['email_recuperacao'])) {
            return false;
        }

        if ($_SESSION['codigo_expira_em'] < time()) {
            $this->limparCodigo();
            return false;
        }

        if ($_SESSION['email_recuperacao'] !== $email) {
            return false;
        }

        if (hash_equals($_SESSION['cod_senha_hash'], hash('sha256', $codigo))) {
            return true;
        }

        $this->registrarErroDeCodigo();

        return false;
    }

    public function limparCodigo(): void
    {
        unset(
            $_SESSION['cod_senha_hash'],
            $_SESSION['codigo_expira_em'],
            $_SESSION['email_recuperacao'],
            $_SESSION['codigo_erros']
        );
    }

    private function registrarCodigoIsca(string $email): void
    {
        $this->guardarCodigo($email, bin2hex(random_bytes(8)));
    }

    private function guardarCodigo(string $email, string $codigo): void
    {
        $_SESSION['cod_senha_hash'] = hash('sha256', $codigo);
        $_SESSION['codigo_expira_em'] = time() + (self::EXPIRACAO_MINUTOS * 60);
        $_SESSION['email_recuperacao'] = $email;
        $_SESSION['codigo_erros'] = 0;
    }

    private function registrarErroDeCodigo(): void
    {
        $_SESSION['codigo_erros'] = ($_SESSION['codigo_erros'] ?? 0) + 1;

        if ($_SESSION['codigo_erros'] >= self::MAX_ERROS_POR_CODIGO) {
            $this->limparCodigo();
        }
    }
}
