<?php

declare(strict_types=1);

namespace Modular\Modules\Usuarios\Domain;


enum Perfil: int
{
    case USER = 1;
    case ADMIN = 2;

    public function toText(): string
    {
        return match ($this) {
            Perfil::ADMIN => 'ADMIN',
            Perfil::USER => 'USER',
        };
    }

    public static function validar(int|string $perfil): self
    {
        $perfilEnum = self::tryFrom($perfil);

        if ($perfilEnum === null) {
            throw UsuarioException::perfilInvalido("Perfil inválido: \"{$perfil}\".");
        }

        return $perfilEnum;
    }
}
