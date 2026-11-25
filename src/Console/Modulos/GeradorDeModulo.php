<?php

declare(strict_types=1);

namespace Src\Console\Modulos;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Src\Console\Migracoes\GeradorDeMigracao;

/**
 * Cria uma funcionalidade nova copiando a funcionalidade "Exemplo" e trocando os nomes.
 *
 * Usar o próprio Exemplo como modelo (em vez de textos-modelo separados) garante que o módulo
 * gerado sempre segue o mesmo padrão do código real. Se surgir um arquivo "Exemplo" novo, o
 * teste GeradorDeModuloTest avisa para incluí-lo em ARQUIVOS_DO_MODELO.
 */
final class GeradorDeModulo
{
    public const ARQUIVOS_DO_MODELO = [
        'src/App/Dto/Exemplo/CriarExemploDto.php',
        'src/App/Dto/Exemplo/EditExemploDto.php',
        'src/App/Http/Controllers/ExemploController.php',
        'src/App/Http/Exceptions/Exemplo/ExemploException.php',
        'src/App/Http/Requests/ExemploRequest.php',
        'src/App/Infrastructure/IRepositories/IExemploRepository.php',
        'src/App/Infrastructure/Repositories/ExemploRepository.php',
        'src/App/Models/Exemplo.php',
        'src/App/Services/Auditoria/AuditoriaExemplo.php',
        'src/App/Services/ExemploService.php',
        'src/App/Services/IServices/IExemploService.php',
        'src/config/dependencies/modulos/exemplo.php',
        'src/routes/modulos/exemplo.php',
        'src/views/Exemplo/criar.php',
        'src/views/Exemplo/editar.php',
        'src/views/Exemplo/index.php',
    ];

    private const PASTA_DE_MIGRATIONS = 'src/database/Migrations';

    /** @param Closure(): DateTimeImmutable $agora */
    public function __construct(
        private readonly string $raiz,
        private readonly Closure $agora
    ) {
    }

    /**
     * @return list<string> caminhos (relativos à raiz) criados; com $simular, os que SERIAM criados
     */
    public function gerar(string $nome, bool $comTabela = false, bool $simular = false): array
    {
        $this->validar($nome);

        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $nome));
        $trocas = ['Exemplo' => $nome, 'exemplo' => $snake, 'EXEMPLO' => strtoupper($snake)];

        $arquivos = [];
        foreach (self::ARQUIVOS_DO_MODELO as $modelo) {
            $origem = $this->raiz . '/' . $modelo;
            if (! is_file($origem)) {
                throw new RuntimeException("Arquivo do modelo não encontrado: {$modelo}");
            }
            $arquivos[strtr($modelo, $trocas)] = strtr((string) file_get_contents($origem), $trocas);
        }

        $migracao = $comTabela ? $this->migracao($nome, $snake) : null;

        $this->recusarSeAlgumDestinoExiste(array_keys($arquivos), $migracao['caminho'] ?? null);

        if (! $simular) {
            foreach ($arquivos as $caminho => $conteudo) {
                $this->gravar($caminho, $conteudo);
            }
            if ($migracao !== null) {
                (new GeradorDeMigracao($this->raiz . '/' . self::PASTA_DE_MIGRATIONS, $this->agora))
                    ->criar($nome, 'criar_tabela_' . $snake, $migracao['sql']);
            }
        }

        return array_merge(array_keys($arquivos), $migracao === null ? [] : [$migracao['caminho']]);
    }

    private function validar(string $nome): void
    {
        if ($nome === 'Exemplo') {
            throw new InvalidArgumentException('"Exemplo" é o modelo usado pelo gerador. Escolha outro nome.');
        }
        if (preg_match('/^[A-Z][a-z0-9]+(?:[A-Z][a-z0-9]+)*$/', $nome) !== 1) {
            throw new InvalidArgumentException("Nome inválido: \"{$nome}\". Use PascalCase, só letras e números (ex.: Produto, ControleDeEstoque).");
        }
    }

    /** @return array{caminho: string, sql: string} */
    private function migracao(string $nome, string $snake): array
    {
        $gerador = new GeradorDeMigracao($this->raiz . '/' . self::PASTA_DE_MIGRATIONS, $this->agora);
        $caminho = self::PASTA_DE_MIGRATIONS . '/' . $gerador->caminhoPara($nome, 'criar_tabela_' . $snake);

        // Mesmas colunas do modelo (tb_exemplo) e só recursos que MySQL e MariaDB aceitam.
        $sql = <<<SQL
CREATE TABLE tb_{$snake} (
  `id`         char(36) NOT NULL,
  `titulo`     varchar(150) NOT NULL,
  `descricao`  varchar(500) DEFAULT NULL,
  `status`     tinyint(1) DEFAULT 1,
  `version`    int unsigned NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SQL;

        return ['caminho' => $caminho, 'sql' => $sql];
    }

    /** @param list<string> $caminhos */
    private function recusarSeAlgumDestinoExiste(array $caminhos, ?string $migracao): void
    {
        $existentes = array_values(array_filter(
            $migracao === null ? $caminhos : array_merge($caminhos, [$migracao]),
            fn (string $caminho): bool => file_exists($this->raiz . '/' . $caminho)
        ));

        if ($existentes !== []) {
            throw new RuntimeException(
                "O módulo não foi criado porque estes arquivos já existem (não sobrescrevo nada):\n  - " . implode("\n  - ", $existentes)
            );
        }
    }

    private function gravar(string $caminho, string $conteudo): void
    {
        $destino = $this->raiz . '/' . $caminho;
        if (! is_dir(dirname($destino)) && ! mkdir(dirname($destino), 0777, true) && ! is_dir(dirname($destino))) {
            throw new RuntimeException("Não consegui criar a pasta de {$caminho}.");
        }
        file_put_contents($destino, $conteudo);
    }
}
