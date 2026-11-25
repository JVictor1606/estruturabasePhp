<?php

declare(strict_types=1);

/**
 * Ferramentas de linha de comando do projeto.
 *
 *   php bin/sgc.php                          lista os comandos
 *   php bin/sgc.php migration:status         o que já foi registrado e o que falta
 *   php bin/sgc.php migration:novo Exemplo novos_campos
 *   php bin/sgc.php modulo Produto --com-tabela
 *   php bin/sgc.php mariadb:gerar            gera a cópia MariaDB dos .sql
 *
 * Atalhos: `composer sgc -- <comando>` ou, no Windows, `sgc <comando>` (sgc.bat).
 */

use Src\Console\Aplicacao;
use Src\Console\Comando\MariadbGerarComando;
use Src\Console\Comando\MigrationMarcarComando;
use Src\Console\Comando\MigrationNovoComando;
use Src\Console\Comando\MigrationStatusComando;
use Src\Console\Comando\ModuloComando;
use Src\Console\Mariadb\ConversorMysqlParaMariadb;
use Src\Console\Migracoes\CatalogoDeMigracoes;
use Src\Console\Migracoes\GeradorDeMigracao;
use Src\Console\Migracoes\RegistroEmArquivoDeMigracoes;
use Src\Console\Modulos\GeradorDeModulo;
use Src\Console\SaidaDeTerminal;

if (PHP_SAPI !== 'cli') {
    exit("Execute pelo terminal: php bin/sgc.php\n");
}

$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

$saida = new SaidaDeTerminal();
$agora = static fn (): DateTimeImmutable => new DateTimeImmutable('now');

$pastaDeMigrations = $raiz . '/src/database/Migrations';
$catalogo = new CatalogoDeMigracoes($pastaDeMigrations);

// O registro do que já foi aplicado é um arquivo local (fora do Git). Nenhum comando usa o banco de dados.
$registro = static fn (): RegistroEmArquivoDeMigracoes => new RegistroEmArquivoDeMigracoes($raiz . '/src/database/.migrations-aplicadas.json', $agora);

$aplicacao = new Aplicacao([
    'migration:status' => new MigrationStatusComando($catalogo, $registro, $saida),
    'migration:novo' => new MigrationNovoComando(new GeradorDeMigracao($pastaDeMigrations, $agora), $saida),
    'migration:marcar' => new MigrationMarcarComando($catalogo, $registro, $saida),
    'modulo' => new ModuloComando(new GeradorDeModulo($raiz, $agora), $saida),
    'mariadb:gerar' => new MariadbGerarComando($raiz, new ConversorMysqlParaMariadb(), $saida, $agora),
], $saida);

exit($aplicacao->executar($argv));
