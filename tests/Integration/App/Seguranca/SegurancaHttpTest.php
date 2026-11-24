<?php

declare(strict_types=1);

namespace Tests\Integration\App\Seguranca;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\HttpTestCase;
use Tests\Support\UserFactory;

/** Defesas que só aparecem na resposta HTTP real: cabeçalhos, cookie de sessão e escape de HTML. */
#[CoversNothing]
#[Group('http')]
#[Group('seguranca')]
final class SegurancaHttpTest extends HttpTestCase
{
    public function testRespostaTrazOsCabecalhosDeSeguranca(): void
    {
        $resposta = $this->novoCliente()->get('/');

        $this->assertSame('DENY', $resposta->cabecalho('X-Frame-Options'));
        $this->assertSame('nosniff', $resposta->cabecalho('X-Content-Type-Options'));
        $this->assertNotNull($resposta->cabecalho('Referrer-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $resposta->cabecalho('Content-Security-Policy'));
    }

    public function testPaginaDeErro404TambemTrazOsCabecalhos(): void
    {
        $resposta = $this->novoCliente()->get('/rota-que-nao-existe');

        $this->assertSame(404, $resposta->status);
        $this->assertSame('DENY', $resposta->cabecalho('X-Frame-Options'));
    }

    public function testNaoAnunciaAVersaoDoPhp(): void
    {
        $this->assertNull($this->novoCliente()->get('/')->cabecalho('X-Powered-By'));
    }

    public function testCookieDeSessaoEHttpOnlyESameSite(): void
    {
        $cookies = $this->novoCliente()->get('/')->cabecalhosComONome('Set-Cookie');

        $this->assertNotEmpty($cookies, 'A página de login deveria abrir a sessão.');
        $sessao = implode(' | ', array_filter($cookies, static fn (string $c): bool => str_starts_with($c, 'PHPSESSID=')));
        $this->assertStringContainsStringIgnoringCase('HttpOnly', $sessao);
        $this->assertStringContainsStringIgnoringCase('SameSite=Lax', $sessao);
    }

    public function testNomeDeUsuarioComHtmlNaoViraHtmlNaPagina(): void
    {
        $nomeMalicioso = '<img src=x onerror=alert(1)>';
        $this->repositorio()->createUser(UserFactory::comum('seg.xss@gmail.com'));
        $this->forcarNome('seg.xss@gmail.com', $nomeMalicioso);

        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();
        $this->limparRateLimitDe('seg.xss@gmail.com');
        $cliente->post('/', ['csrf' => $token, 'email' => 'seg.xss@gmail.com', 'senha' => UserFactory::SENHA_PADRAO]);

        $home = $cliente->get('/home');

        $this->assertSame(200, $home->status);
        $this->assertFalse($home->contem('<img src=x'), 'O nome do usuário foi impresso sem escape.');
        $this->assertTrue($home->contem('&lt;img src=x'));
    }

    public function testNomeComHtmlEscapadoTambemNasTelasDeAdministracao(): void
    {
        $this->repositorio()->createUser(UserFactory::admin('seg.adm2@gmail.com'));
        $vitima = $this->repositorio()->createUser(UserFactory::comum('seg.vitima@gmail.com'));
        $this->forcarNome('seg.vitima@gmail.com', '"><script>alert(document.cookie)</script>');

        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();
        $this->limparRateLimitDe('seg.adm2@gmail.com');
        $cliente->post('/', ['csrf' => $token, 'email' => 'seg.adm2@gmail.com', 'senha' => UserFactory::SENHA_PADRAO]);

        foreach (['/admin', '/admin/detalhes/' . $vitima->getId(), '/admin/editar/' . $vitima->getId()] as $rota) {
            $pagina = $cliente->get($rota);

            $this->assertSame(200, $pagina->status, $rota);
            $this->assertFalse($pagina->contem('<script>alert(document.cookie)</script>'), "A rota {$rota} imprimiu o nome sem escape.");
        }
    }

    public function testNenhumaPaginaDeixaPlaceholderSemSubstituir(): void
    {
        $admin = $this->repositorio()->createUser(UserFactory::admin('seg.admin@gmail.com'));
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();
        $this->limparRateLimitDe('seg.admin@gmail.com');
        $cliente->post('/', ['csrf' => $token, 'email' => 'seg.admin@gmail.com', 'senha' => UserFactory::SENHA_PADRAO]);

        foreach (['/home', '/admin', '/admin/novo', '/admin/editar/' . $admin->getId(), '/admin/detalhes/' . $admin->getId(), '/configuracoes', '/exemplo', '/exemplo/criar', '/rota-que-nao-existe'] as $rota) {
            $pagina = $cliente->get($rota);
            $this->assertDoesNotMatchRegularExpression('/\{\{\w+\}\}/', $pagina->corpo, "A rota {$rota} deixou um {{placeholder}} sem substituir.");
        }
    }

    /** Grava o nome direto no banco, simulando dado antigo ou criado por um caminho sem sanitização. */
    private function forcarNome(string $email, string $nome): void
    {
        $stmt = self::$pdo->prepare('UPDATE tb_usuarios SET nome = :nome WHERE email = :email');
        $stmt->execute([':nome' => $nome, ':email' => $email]);
    }
}
