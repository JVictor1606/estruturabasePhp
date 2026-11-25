<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

/** A "memória" do que já foi aplicado. Hoje é um arquivo local; poderia ser outra coisa sem mudar os comandos. */
interface IRegistroDeMigracoes
{
    /** @return array<string, array{checksum: string, executada_em: string}> caminho => dados */
    public function aplicadas(): array;

    /** @throws \RuntimeException se o caminho já estiver registrado */
    public function registrar(string $caminho, string $checksum): void;

    /** Onde o registro está guardado (aparece na tela, para a pessoa saber o que está sendo consultado). */
    public function descricao(): string;
}
