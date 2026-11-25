<?php

declare(strict_types=1);

namespace Src\Console\Mariadb;

/**
 * Gera a versão MariaDB de um SQL escrito para o MySQL 8.
 *
 * Hoje troca as collations utf8mb4_0900_* (só existem no MySQL 8) pelas equivalentes do MariaDB e
 * AVISA do que não consegue converter sozinho. Só mexe no que vem depois da palavra COLLATE, então
 * um texto de dado que apenas cite o nome de uma collation não é alterado.
 */
final class ConversorMysqlParaMariadb
{
    /** sufixo da collation do MySQL 8 => [sufixo no MariaDB, é só uma aproximação?] */
    private const EQUIVALENTES = [
        'ai_ci' => ['unicode_ci', false],
        'as_ci' => ['unicode_ci', true],
        'as_cs' => ['bin', true],
        'bin' => ['bin', false],
    ];

    public function converter(string $sql): ResultadoDaConversao
    {
        $trocas = [];
        $avisos = [];

        $convertido = preg_replace_callback(
            '/(COLLATE\s*=?\s*)(utf8mb4|utf8mb3|utf8)_0900_(ai_ci|as_ci|as_cs|bin)\b/i',
            static function (array $achado) use (&$trocas, &$avisos): string {
                $charset = strtolower($achado[2]);
                $sufixo = strtolower($achado[3]);
                [$novoSufixo, $aproximacao] = self::EQUIVALENTES[$sufixo];

                $antigo = "{$charset}_0900_{$sufixo}";
                $novo = "{$charset}_{$novoSufixo}";
                $trocas["{$antigo} → {$novo}"] = ($trocas["{$antigo} → {$novo}"] ?? 0) + 1;

                if ($aproximacao) {
                    $avisos["{$antigo}"] = "{$antigo} foi trocada por {$novo}, que é só uma aproximação: confira a ordenação e as comparações de texto.";
                }

                return $achado[1] . $novo;
            },
            $sql
        ) ?? $sql;

        $mudancas = [];
        foreach ($trocas as $descricao => $quantidade) {
            $mudancas[] = "{$quantidade}× {$descricao}";
        }

        if (preg_match('/\bDROP\s+TABLE\b/i', $sql) === 1) {
            $avisos['drop_table'] = 'Contém DROP TABLE: rodar este arquivo APAGA as tabelas que já existem.';
        }
        if (preg_match('/\bDROP\s+DATABASE\b/i', $sql) === 1) {
            $avisos['drop_database'] = 'Contém DROP DATABASE: rodar este arquivo APAGA o banco inteiro.';
        }

        return new ResultadoDaConversao($convertido, $mudancas, array_values($avisos));
    }
}
