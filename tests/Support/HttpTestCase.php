<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Core\RateLimiting;

/** Base dos testes ponta a ponta: um servidor por classe de teste, banco *_test limpo por teste. */
abstract class HttpTestCase extends DatabaseTestCase
{
    private static ?LocalServer $servidor = null;

    /** @var list<string> chaves de rate limit criadas pelo teste, removidas no tearDown */
    private array $chavesRateLimit = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! Rede::dnsDisponivel()) {
            self::markTestSkipped('Sem DNS: a validação de e-mail do login consulta o MX do domínio.');
        }

        self::$servidor = LocalServer::iniciar(TestDatabase::nome());
    }

    public static function tearDownAfterClass(): void
    {
        self::$servidor?->parar();
        self::$servidor = null;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$servidor?->limparEmails();
    }

    protected function tearDown(): void
    {
        $limitador = new RateLimiting();
        foreach ($this->chavesRateLimit as $chave) {
            $limitador->limpar($chave);
        }
        $this->chavesRateLimit = [];
    }

    /** @return list<array{para: string, assunto: string, corpo: string}> */
    protected function emailsEnviados(): array
    {
        return self::$servidor?->emailsEnviados() ?? [];
    }

    /** Código de 6 dígitos do último e-mail enviado ao endereço (o template o mostra como "123 - 456"). */
    protected function ultimoCodigoEnviadoPara(string $email): string
    {
        $para = array_values(array_filter($this->emailsEnviados(), static fn (array $m): bool => $m['para'] === $email));
        $ultimo = end($para);
        preg_match('/(\d{3})\s*-\s*(\d{3})/', strip_tags((string) ($ultimo['corpo'] ?? '')), $achado);

        return ($achado[1] ?? '') . ($achado[2] ?? '');
    }

    protected function novoCliente(): HttpClient
    {
        return new HttpClient((string) self::$servidor?->url());
    }

    /** Registra o e-mail para limpar o contador de tentativas de login ao fim do teste. */
    protected function limparRateLimitDe(string $email): void
    {
        $email = strtolower(trim($email));
        foreach (['login', 'recuperacao'] as $escopo) {
            $this->chavesRateLimit[] = "{$escopo}#127.0.0.1#{$email}";
        }
    }
}
