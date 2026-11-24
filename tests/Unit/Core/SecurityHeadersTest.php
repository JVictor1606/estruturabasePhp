<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Src\Core\SecurityHeaders;
use Src\Core\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(SecurityHeaders::class)]
#[CoversClass(Session::class)]
#[Group('seguranca')]
final class SecurityHeadersTest extends TestCase
{
    public function testImpedeEmbutirAPaginaEmOutroSiteClickjacking(): void
    {
        $cabecalhos = SecurityHeaders::cabecalhos();

        $this->assertSame('DENY', $cabecalhos['X-Frame-Options']);
        $this->assertStringContainsString("frame-ancestors 'none'", $cabecalhos['Content-Security-Policy']);
    }

    public function testNavegadorNaoAdivinhaTipoDeConteudo(): void
    {
        $this->assertSame('nosniff', SecurityHeaders::cabecalhos()['X-Content-Type-Options']);
    }

    public function testNaoVazaUrlCompletaParaOutrosSites(): void
    {
        $this->assertSame('strict-origin-when-cross-origin', SecurityHeaders::cabecalhos()['Referrer-Policy']);
    }

    public function testPoliticaDeConteudoBloqueiaOQueNaoPrecisa(): void
    {
        $csp = SecurityHeaders::cabecalhos()['Content-Security-Policy'];

        foreach (["default-src 'self'", "object-src 'none'", "base-uri 'self'", "form-action 'self'"] as $diretiva) {
            $this->assertStringContainsString($diretiva, $csp);
        }
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringNotContainsString(' * ', $csp . ' ', 'Curinga libera qualquer origem.');
        $this->assertStringNotContainsString('http:', $csp, 'Nenhuma origem sem HTTPS.');
    }

    public function testNaoEnviaHstsParaQueCertificadoVencidoPermitaAbrirMesmoAssim(): void
    {
        // Decisão do projeto: sem HSTS o navegador mostra "continuar mesmo assim" num certificado vencido.
        $this->assertArrayNotHasKey('Strict-Transport-Security', SecurityHeaders::cabecalhos());
        $this->assertArrayNotHasKey('Strict-Transport-Security', SecurityHeaders::cabecalhos());
    }

    public function testCookieDeSessaoNaoEhLegivelPorJavaScriptENemEnviadoEmSiteExterno(): void
    {
        $opcoes = Session::opcoesDoCookie(false);

        $this->assertTrue($opcoes['httponly']);
        $this->assertSame('Lax', $opcoes['samesite']);
        $this->assertFalse($opcoes['secure'], 'Sem HTTPS o cookie Secure nunca chegaria ao servidor.');
        $this->assertTrue(Session::opcoesDoCookie(true)['secure']);
    }
}
