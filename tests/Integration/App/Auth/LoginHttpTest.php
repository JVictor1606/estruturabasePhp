<?php

declare(strict_types=1);

namespace Tests\Integration\App\Auth;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\HttpClient;
use Tests\Support\HttpResponse;
use Tests\Support\HttpTestCase;
use Tests\Support\UserFactory;

/**
 * Fluxo de login ponta a ponta: HTTP real -> Router -> middlewares -> controller -> service -> MySQL de teste.
 * Os e-mails usam gmail.com porque a validação de e-mail consulta o MX do domínio (nenhuma mensagem é enviada).
 */
#[CoversNothing]
#[Group('http')]
final class LoginHttpTest extends HttpTestCase
{
    private const SENHA = UserFactory::SENHA_PADRAO;

    public function testTelaDeLoginCarregaComTokenCsrf(): void
    {
        $resposta = $this->novoCliente()->get('/');

        $this->assertSame(200, $resposta->status);
        $this->assertNotNull($resposta->tokenCsrf());
    }

    public function testUsuarioComumLogaEVaiParaHome(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.comum@gmail.com'));
        $cliente = $this->novoCliente();

        $login = $this->tentarLogin($cliente, 'login.comum@gmail.com', self::SENHA);

        $this->assertSame(302, $login->status);
        $this->assertSame('/home', $login->location());
        $this->assertSame(200, $cliente->get('/home')->status);
    }

    public function testAdminLogaEVaiParaPainelAdmin(): void
    {
        $this->repositorio()->createUser(UserFactory::admin('login.admin@gmail.com'));
        $cliente = $this->novoCliente();

        $login = $this->tentarLogin($cliente, 'login.admin@gmail.com', self::SENHA);

        $this->assertSame('/admin', $login->location());
        $this->assertSame(200, $cliente->get('/admin')->status);
    }

    public function testSenhaErradaNaoAutenticaEMostraErroGenerico(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.erro@gmail.com'));
        $cliente = $this->novoCliente();

        $login = $this->tentarLogin($cliente, 'login.erro@gmail.com', 'Errada@123');

        $this->assertSame('/', $login->location());
        $this->assertTrue($cliente->get('/')->contem('Usuário ou senha incorretos'));
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testEmailInexistenteTemMesmaMensagemQueSenhaErrada(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.existe@gmail.com'));

        $porSenha = $this->novoCliente();
        $this->tentarLogin($porSenha, 'login.existe@gmail.com', 'Errada@123');

        $porEmail = $this->novoCliente();
        $this->tentarLogin($porEmail, 'login.naoexiste@gmail.com', self::SENHA);

        $this->assertTrue($porSenha->get('/')->contem('Usuário ou senha incorretos'));
        $this->assertTrue($porEmail->get('/')->contem('Usuário ou senha incorretos'));
    }

    public function testUsuarioInativoNaoLogaMesmoComSenhaCorreta(): void
    {
        $this->repositorio()->createUser(UserFactory::inativo('login.inativo@gmail.com'));
        $cliente = $this->novoCliente();

        $login = $this->tentarLogin($cliente, 'login.inativo@gmail.com', self::SENHA);

        $this->assertSame('/', $login->location());
        $this->assertTrue($cliente->get('/')->contem('Usuário ou senha incorretos'));
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testDadosInvalidosVoltamParaOLoginSemAutenticar(): void
    {
        $cliente = $this->novoCliente();

        $login = $this->tentarLogin($cliente, 'isso-nao-e-email', '123');

        $this->assertSame(302, $login->status);
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testPostSemTokenCsrfEhRecusadoMesmoComCredenciaisCorretas(): void
    {
        $this->limparRateLimitDe('login.csrf@gmail.com');
        $this->repositorio()->createUser(UserFactory::comum('login.csrf@gmail.com'));
        $cliente = $this->novoCliente();
        $cliente->get('/');

        $login = $cliente->post('/', ['email' => 'login.csrf@gmail.com', 'senha' => self::SENHA]);

        $this->assertSame('/', $login->location());
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testPostComTokenCsrfDeOutraSessaoEhRecusado(): void
    {
        $this->limparRateLimitDe('login.csrf2@gmail.com');
        $this->repositorio()->createUser(UserFactory::comum('login.csrf2@gmail.com'));
        $tokenDeOutraSessao = $this->novoCliente()->get('/')->tokenCsrf();

        $cliente = $this->novoCliente();
        $cliente->get('/');
        $login = $cliente->post('/', [
            'csrf' => (string) $tokenDeOutraSessao,
            'email' => 'login.csrf2@gmail.com',
            'senha' => self::SENHA,
        ]);

        $this->assertSame('/', $login->location());
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testLoginTrocaOIdDaSessaoContraFixation(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.sessao@gmail.com'));
        $cliente = $this->novoCliente();
        $cliente->get('/');
        $antes = $cliente->cookie('PHPSESSID');

        $this->tentarLogin($cliente, 'login.sessao@gmail.com', self::SENHA);

        $this->assertNotNull($antes);
        $this->assertNotNull($cliente->cookie('PHPSESSID'));
        $this->assertNotSame($antes, $cliente->cookie('PHPSESSID'));
    }

    public function testRotasProtegidasRedirecionamVisitanteParaOLogin(): void
    {
        $cliente = $this->novoCliente();

        foreach (['/home', '/admin', '/exemplo', '/configuracoes'] as $rota) {
            $this->assertSame('/', $cliente->get($rota)->location(), "Rota {$rota} deveria exigir login.");
        }
    }

    public function testUsuarioComumNaoAcessaAreaAdmin(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.semadmin@gmail.com'));
        $cliente = $this->novoCliente();
        $this->tentarLogin($cliente, 'login.semadmin@gmail.com', self::SENHA);

        $this->assertSame('/home', $cliente->get('/admin')->location());
    }

    public function testUsuarioLogadoNaoVeAtelaDeLoginDeNovo(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.guest@gmail.com'));
        $cliente = $this->novoCliente();
        $this->tentarLogin($cliente, 'login.guest@gmail.com', self::SENHA);

        $this->assertSame('/home', $cliente->get('/')->location());
    }

    public function testLogoutEncerraASessao(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.logout@gmail.com'));
        $cliente = $this->novoCliente();
        $this->tentarLogin($cliente, 'login.logout@gmail.com', self::SENHA);
        $this->assertSame(200, $cliente->get('/home')->status);

        $logout = $cliente->get('/logout');

        $this->assertSame('/', $logout->location());
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testUsuarioDesativadoComSessaoAbertaEExpulso(): void
    {
        $user = $this->repositorio()->createUser(UserFactory::comum('login.desativado@gmail.com'));
        $cliente = $this->novoCliente();
        $this->tentarLogin($cliente, 'login.desativado@gmail.com', self::SENHA);
        $this->assertSame(200, $cliente->get('/home')->status);

        $user->setStatus(\Src\App\Enums\Status::INATIVO);
        $this->repositorio()->updateUser($user);

        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testBloqueiaDepoisDeCincoTentativasFalhasMesmoComSenhaCorreta(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.limite@gmail.com'));
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $cliente->post('/', $this->credenciais($token, 'login.limite@gmail.com', 'Errada@123'));
        }
        $cliente->get('/');

        $sexta = $cliente->post('/', $this->credenciais($token, 'login.limite@gmail.com', self::SENHA));

        $this->assertSame('/', $sexta->location());
        $this->assertTrue($cliente->get('/')->contem('Muitas tentativas'));
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    /** Regressão: o login com sucesso chamava limpar() com a chave errada e nunca zerava o contador. */
    public function testLoginBemSucedidoZeraOContadorDeTentativas(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.zera@gmail.com'));
        $cliente = $this->novoCliente();

        // 4 falhas + 1 sucesso = 5 tentativas registradas. O sucesso deveria zerar o contador.
        for ($falha = 1; $falha <= 4; $falha++) {
            $this->tentarLogin($cliente, 'login.zera@gmail.com', 'Errada@123');
        }
        $this->tentarLogin($cliente, 'login.zera@gmail.com', self::SENHA);
        $cliente->get('/logout');

        $this->tentarLogin($cliente, 'login.zera@gmail.com', 'Errada@123');

        $tela = $cliente->get('/');
        $this->assertFalse($tela->contem('Muitas tentativas'), 'O contador não foi zerado após o login com sucesso.');
        $this->assertTrue($tela->contem('Usuário ou senha incorretos'));
    }

    public function testBloqueioNaoEDriblavelTrocandoMaiusculasEEspacosDoEmail(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.caixa@gmail.com'));
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();

        foreach (['LOGIN.CAIXA@gmail.com', 'Login.Caixa@gmail.com', ' login.caixa@gmail.com', 'LOGIN.caixa@GMAIL.com', 'login.caixa@gmail.com'] as $variacao) {
            $cliente->post('/', $this->credenciais($token, $variacao, 'Errada@123'));
        }

        $cliente->post('/', $this->credenciais($token, 'login.caixa@gmail.com', self::SENHA));

        $this->assertTrue($cliente->get('/')->contem('Muitas tentativas'), 'Variar maiúsculas/espaços não pode zerar o contador.');
        $this->assertSame('/', $cliente->get('/home')->location());
    }

    public function testEmailDigitadoVoltaPreenchidoDepoisDeErrarASenhaMasASenhaNunca(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('login.repovoa@gmail.com'));
        $cliente = $this->novoCliente();
        $this->tentarLogin($cliente, 'login.repovoa@gmail.com', 'Errada@123');

        $tela = $cliente->get('/');

        $this->assertTrue($tela->contem('value="login.repovoa@gmail.com"'));
        $this->assertFalse($tela->contem('Errada@123'));
    }

    /** Busca o token na tela de login e envia o formulário, como um navegador faria. */
    private function tentarLogin(HttpClient $cliente, string $email, string $senha): HttpResponse
    {
        $token = (string) $cliente->get('/')->tokenCsrf();

        return $cliente->post('/', $this->credenciais($token, $email, $senha));
    }

    /** @return array<string, string> */
    private function credenciais(string $token, string $email, string $senha): array
    {
        $this->limparRateLimitDe($email);

        return ['csrf' => $token, 'email' => $email, 'senha' => $senha];
    }
}
