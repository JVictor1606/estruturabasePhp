<?php

declare(strict_types=1);

namespace Modular\Modules\Auditoria\Services;

interface IAuditoriaService
{
    public function registrar(?string $usuarioId, string $modulo, string $acao, string $id, array $detalhe): void;
    public function getLastRegistros(int $limit): array;
}
