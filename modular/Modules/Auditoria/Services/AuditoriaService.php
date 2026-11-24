<?php

declare(strict_types=1);

namespace Modular\Modules\Auditoria\Services;

use Modular\Modules\Auditoria\Domain\IAuditoriaRepository;

class AuditoriaService implements IAuditoriaService
{
    private IAuditoriaRepository $auditoriaRepository;

    public function __construct(IAuditoriaRepository $auditoriaRepository)
    {
        $this->auditoriaRepository = $auditoriaRepository;
    }
    public function registrar(?string $usuarioId, string $modulo, string $acao, string $id, array $detalhe): void
    {
        $this->auditoriaRepository->create([
            'usuario_id' => $usuarioId,
            'modulo' => $modulo,
            'acao' => $acao,
            'registro_id' => $id,
            'detalhe' => $detalhe,
        ]);
    }
    public function getLastRegistros(int $limit): array
    {
        return $this->auditoriaRepository->getlastRegistros($limit);
    }
}
