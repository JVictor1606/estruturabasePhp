<?php

declare(strict_types=1);

namespace Modular\Modules\Auditoria\Services;


class NullAuditoriaService implements IAuditoriaService
{
    public function registrar(?string $usuarioId, string $modulo, string $acao, string $id, array $detalhe): void
    {
        // sem implementação de auditoria por enquanto
    }

    public function getLastRegistros(int $limit): array
    {
        return [];
    }
}
