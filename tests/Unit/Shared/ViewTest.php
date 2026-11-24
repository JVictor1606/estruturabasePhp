<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use Modular\Shared\Support\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Segurança do motor de views: placeholders {{x}} escapam por padrão (XSS) e não se reexpandem. */
#[CoversClass(View::class)]
final class ViewTest extends TestCase
{
    public function testPlaceholderEscapaHtmlPorPadrao(): void
    {
        $html = View::render('Shared/header', ['usuario' => '<img src=x onerror=alert(1)>']);

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testPlaceholderEscapaAspasParaNaoQuebrarAtributos(): void
    {
        $html = View::render('Shared/header', ['usuario' => '" onmouseover="alert(1)']);

        $this->assertStringNotContainsString('" onmouseover="', $html);
    }

    public function testNaoReexpandePlaceholderDentroDeValorDoUsuario(): void
    {
        // Antes, o nome "{{toast}}" era trocado numa passada seguinte pelo conteúdo da variável toast.
        $html = View::render('Shared/header', ['usuario' => '{{toast}}', 'toast' => '<script>x</script>']);

        $this->assertStringNotContainsString('<script>x</script>', $html);
    }

    public function testPlaceholderDesconhecidoPermaneceIntacto(): void
    {
        $html = View::render('Shared/header', []);

        $this->assertStringContainsString('{{usuario}}', $html);
    }
}
