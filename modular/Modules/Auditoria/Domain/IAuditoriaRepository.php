<?php

declare(strict_types=1);

namespace Modular\Modules\Auditoria\Domain;

interface IAuditoriaRepository
{
    public function create(array $data): void;
    public function getlastRegistros(int $limit): array;
}
