<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Aplicacao;
use Src\Console\Comando\IComando;
use Tests\Support\SaidaEmMemoria;

#[CoversClass(Aplicacao::class)]
final class AplicacaoTest extends TestCase
{
    private SaidaEmMemoria $saida;

    protected function setUp(): void
    {
        $this->saida = new SaidaEmMemoria();
    }

    /** Comando de mentira que só anota com o que foi chamado. */
    private function espiao(int $retorno = 0): object
    {
        return new class ($retorno) implements IComando {
            public array $argumentos = [];
            public array $opcoes = [];
            public int $chamadas = 0;

            public function __construct(private readonly int $retorno)
            {
            }

            public function descricao(): string
            {
                return 'comando de teste';
            }

            public function executar(array $argumentos, array $opcoes): int
            {
                $this->chamadas++;
                $this->argumentos = $argumentos;
                $this->opcoes = $opcoes;

                return $this->retorno;
            }
        };
    }

    public function testSeparaArgumentosDeOpcoes(): void
    {
        $modulo = $this->espiao();
        $app = new Aplicacao(['modulo' => $modulo], $this->saida);

        $app->executar(['sgc', 'modulo', 'Produto', '--dry-run', '--com-tabela']);

        $this->assertSame(['Produto'], $modulo->argumentos);
        $this->assertSame(['dry-run' => true, 'com-tabela' => true], $modulo->opcoes);
    }

    public function testOpcaoComValorUsaIgual(): void
    {
        $cmd = $this->espiao();
        $app = new Aplicacao(['x' => $cmd], $this->saida);

        $app->executar(['sgc', 'x', '--area=Exemplo']);

        $this->assertSame(['area' => 'Exemplo'], $cmd->opcoes);
    }

    public function testAtalhoDoisTracosModuloEquivaleAoComandoModulo(): void
    {
        $modulo = $this->espiao();
        $app = new Aplicacao(['modulo' => $modulo], $this->saida);

        $app->executar(['sgc', '--modulo', 'Exemplo2']);

        $this->assertSame(1, $modulo->chamadas);
        $this->assertSame(['Exemplo2'], $modulo->argumentos);
    }

    public function testDevolveOCodigoDeSaidaDoComando(): void
    {
        $app = new Aplicacao(['x' => $this->espiao(3)], $this->saida);

        $this->assertSame(3, $app->executar(['sgc', 'x']));
    }

    public function testComandoDesconhecidoMostraAjudaEFalha(): void
    {
        $app = new Aplicacao(['x' => $this->espiao()], $this->saida);

        $codigo = $app->executar(['sgc', 'inexistente']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString("Comando desconhecido: inexistente", $this->saida->texto());
        $this->assertStringContainsString('x', $this->saida->texto());
    }

    public function testSemArgumentosMostraAjudaComDescricoes(): void
    {
        $app = new Aplicacao(['x' => $this->espiao()], $this->saida);

        $codigo = $app->executar(['sgc']);

        $this->assertSame(0, $codigo);
        $this->assertStringContainsString('comando de teste', $this->saida->texto());
    }

    public function testErroDeUsoViraMensagemSemStackTraceEFalha(): void
    {
        $quebra = new class implements IComando {
            public function descricao(): string
            {
                return 'quebra';
            }

            public function executar(array $argumentos, array $opcoes): int
            {
                throw new InvalidArgumentException('Nome inválido.');
            }
        };
        $app = new Aplicacao(['quebra' => $quebra], $this->saida);

        $codigo = $app->executar(['sgc', 'quebra']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('Erro: Nome inválido.', $this->saida->texto());
        $this->assertStringNotContainsString('#0', $this->saida->texto());
    }
}
