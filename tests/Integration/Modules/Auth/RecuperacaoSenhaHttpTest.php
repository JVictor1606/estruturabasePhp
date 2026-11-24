<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Auth;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\HttpClient;
use Tests\Support\HttpResponse;
use Tests\Support\HttpTestCase;
use Tests\Support\UserFactory;

/**
 * Recuperação de senha ponta a ponta. O servidor roda com EMAIL_DRIVER=arquivo:
 * nenhum e-mail de verdade é enviado e o código é lido do arquivo.
 */
#[CoversNothing]
#[Group('http')]
#[Group('seguranca')]
final class RecuperacaoSenhaHttpTest extends HttpTestCase
{
    private const EXISTE = 'rec.existe@gmail.com';
    private const FANTASMA = 'rec.fantasma@gmail.com';

    public function testFluxoCompletoRedefineASenhaEPermiteEntrarComAnova(): void
    {
        $this->repositorio()->createUser(UserFactory::comum(self::EXISTE, 'Antiga@123'));
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/esqueci-senha')->tokenCsrf();

        $this->pedirCodigo($cliente, $token, self::EXISTE);
        $codigo = $this->ultimoCodigoEnviadoPara(self::EXISTE);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codigo);

        $verificacao = $this->enviarCodigo($cliente, $token, self::EXISTE, $codigo);
        $this->assertTrue($verificacao->contem('name="nova_senha"'), 'Código certo deveria liberar o formulário da nova senha.');

        $cliente->post('/esqueci-senha/redefinir', ['csrf' => $token, 'email' => self::EXISTE, 'nova_senha' => 'Nova@Senha1', 'confirmar_senha' => 'Nova@Senha1']);

        $this->assertSame('/', $this->login(self::EXISTE, 'Antiga@123')->location(), 'A senha antiga não pode mais valer.');
        $this->assertSame('/home', $this->login(self::EXISTE, 'Nova@Senha1')->location());
    }

    public function testResponderIgualParaEmailCadastradoENaoCadastrado(): void
    {
        $this->repositorio()->createUser(UserFactory::comum(self::EXISTE));

        $existente = $this->pedirCodigoComClienteNovo(self::EXISTE);
        $fantasma = $this->pedirCodigoComClienteNovo(self::FANTASMA);

        foreach ([$existente, $fantasma] as $resposta) {
            $this->assertSame(200, $resposta->status);
            $this->assertTrue($resposta->contem('code-input'), 'Deveria mostrar a tela de digitar o código.');
            $this->assertFalse($resposta->contem('não encontrado'));
            $this->assertFalse($resposta->contem('não cadastrado'));
        }

        $this->assertSame(
            $this->normalizar($existente->corpo, self::EXISTE),
            $this->normalizar($fantasma->corpo, self::FANTASMA),
            'As duas respostas precisam ser indistinguíveis, senão dá para listar os e-mails cadastrados.'
        );
    }

    public function testSoEmailCadastradoRecebeMensagem(): void
    {
        $this->repositorio()->createUser(UserFactory::comum(self::EXISTE));

        $this->pedirCodigoComClienteNovo(self::FANTASMA);
        $this->assertSame([], $this->emailsEnviados(), 'E-mail não cadastrado não recebe nada.');

        $this->pedirCodigoComClienteNovo(self::EXISTE);
        $this->assertCount(1, $this->emailsEnviados());
        $this->assertSame(self::EXISTE, $this->emailsEnviados()[0]['para']);
    }

    public function testCodigoDeSeisDigitosNaoPodeSerAdivinhado(): void
    {
        $this->repositorio()->createUser(UserFactory::comum(self::EXISTE));
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/esqueci-senha')->tokenCsrf();
        $this->pedirCodigo($cliente, $token, self::EXISTE);
        $certo = $this->ultimoCodigoEnviadoPara(self::EXISTE);
        $errado = $certo === '000000' ? '111111' : '000000';

        for ($palpite = 1; $palpite <= 5; $palpite++) {
            $this->enviarCodigo($cliente, $token, self::EXISTE, $errado);
        }
        $depois = $this->enviarCodigo($cliente, $token, self::EXISTE, $certo);

        $this->assertFalse($depois->contem('name="nova_senha"'), 'Depois de vários erros o código certo não pode mais valer.');
        $this->assertTrue($cliente->get('/esqueci-senha')->contem('Muitas tentativas'));
    }

    public function testPedirCodigoVariasVezesParaOMesmoEmailTemLimite(): void
    {
        $this->repositorio()->createUser(UserFactory::comum(self::EXISTE));

        for ($pedido = 1; $pedido <= 8; $pedido++) {
            $this->pedirCodigoComClienteNovo(self::EXISTE);
        }

        $this->assertCount(5, $this->emailsEnviados(), 'Sem limite, 8 pedidos mandariam 8 e-mails para a caixa da vítima.');
    }

    private function pedirCodigoComClienteNovo(string $email): HttpResponse
    {
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/esqueci-senha')->tokenCsrf();

        return $this->pedirCodigo($cliente, $token, $email);
    }

    private function pedirCodigo(HttpClient $cliente, string $token, string $email): HttpResponse
    {
        $this->limparRateLimitDe($email);

        return $cliente->post('/esqueci-senha/verificar', ['csrf' => $token, 'email' => $email]);
    }

    private function enviarCodigo(HttpClient $cliente, string $token, string $email, string $codigo): HttpResponse
    {
        return $cliente->post('/esqueci-senha/verificar-codigo', ['csrf' => $token, 'email' => $email, 'codigo' => $codigo]);
    }

    private function login(string $email, string $senha): HttpResponse
    {
        $this->limparRateLimitDe($email);
        $cliente = $this->novoCliente();
        $token = (string) $cliente->get('/')->tokenCsrf();

        return $cliente->post('/', ['csrf' => $token, 'email' => $email, 'senha' => $senha]);
    }

    /** Remove o que legitimamente muda de uma resposta para outra (e-mail, token, horários). */
    private function normalizar(string $html, string $email): string
    {
        $html = str_replace($email, '{EMAIL}', $html);
        $html = (string) preg_replace('/name="csrf" value="[^"]+"/', 'name="csrf" value="{TOKEN}"', $html);

        return (string) preg_replace('/\b1[0-9]{9}\b/', '{TEMPO}', $html);
    }
}
