<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Services;

interface IRecuperacaoSenhaService
{
    public function enviarCodigoRecuperacao(string $email): void;

    public function solicitarCodigoRecuperacao(string $email): void;

    public function verificarCodigo(string $email, string $codigo): bool;

    public function limparCodigo(): void;
}
