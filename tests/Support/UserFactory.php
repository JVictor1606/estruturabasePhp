<?php

declare(strict_types=1);

namespace Tests\Support;

use Modular\Modules\Usuarios\Domain\Perfil;
use Modular\Modules\Usuarios\Domain\Senha;
use Modular\Modules\Usuarios\Domain\Status;
use Modular\Modules\Usuarios\Domain\User;

/** Monta usuários de teste sem repetir o construtor em cada teste. */
final class UserFactory
{
    public const SENHA_PADRAO = 'Senha@123';

    public static function comum(string $email, string $senha = self::SENHA_PADRAO): User
    {
        return self::criar($email, $senha, Perfil::USER, Status::ATIVO);
    }

    public static function admin(string $email, string $senha = self::SENHA_PADRAO): User
    {
        return self::criar($email, $senha, Perfil::ADMIN, Status::ATIVO);
    }

    public static function inativo(string $email, string $senha = self::SENHA_PADRAO): User
    {
        return self::criar($email, $senha, Perfil::USER, Status::INATIVO);
    }

    /** @var array<string, string> hashes baratos reaproveitados entre usuários de teste */
    private static array $hashes = [];

    private static function criar(string $email, string $senha, Perfil $perfil, Status $status): User
    {
        return new User(
            nome: 'Usuario Teste',
            email: $email,
            senha: Senha::senhaHash(self::hashBarato($senha)),
            perfil: $perfil,
            status: $status,
        );
    }

    /**
     * Fixture não precisa do custo de produção: bcrypt com cost 4 cai de ~300 ms para ~1 ms.
     * O hash real (Senha::senha) continua coberto em SenhaTest.
     */
    private static function hashBarato(string $senha): string
    {
        return self::$hashes[$senha] ??= password_hash($senha, PASSWORD_BCRYPT, ['cost' => 4]);
    }
}
