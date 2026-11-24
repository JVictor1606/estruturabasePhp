<?php

declare(strict_types=1);

namespace Modular\Modules\Exemplo\Services;

use Modular\Modules\Exemplo\Domain\Exemplo;
use Modular\Modules\Exemplo\Dto\CriarExemploDto;
use Modular\Modules\Exemplo\Dto\EditExemploDto;

interface IExemploService
{
    public function create(CriarExemploDto $dto): Exemplo;
    public function update(EditExemploDto $dto): Exemplo;
    public function delete(string $id): bool;
    public function getAll(): array;
    public function getById(string $id): Exemplo;
    public function count(): int;
}
