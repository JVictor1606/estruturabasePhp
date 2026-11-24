# Guia da base — MVC em camadas (`src/`)

Leia isto antes de escrever código aqui. Vale para pessoas e para o Claude Code.
Idioma: português brasileiro nos nomes de domínio, mensagens e comentários.

## 1. Estrutura oficial hoje e o que vem depois

- Esta branch roda **só** o `src/` (MVC em camadas). `public/index.php` carrega `src/config/dependencies.php` e `src/routes/web.php`.
- Existe uma versão em **monolito modular** na branch `modular`. A migração para ela está prevista. Para facilitar, mantenha as fronteiras de camada limpas (seção 3): cada controller, service e repositório de hoje vira peça de um módulo amanhã.
- Depois de clonar: `composer install` (gera o autoload, que não vai no Git) e copie `.env.example` para `.env`.
- Variáveis do `.env` que mudam o comportamento: `DB_*`, `CHAVE_EMAIL`, `EMAIL_DRIVER` (`http` envia de verdade; `arquivo` grava em `EMAIL_ARQUIVO`, use no desenvolvimento para não disparar e-mail real) e `DB_NAME_TEST` (banco dos testes).

## 2. Mapa

```
src/
├── Core/                  Infraestrutura do framework (Router, Container, Database, Csrf, Request, Session, SecurityHeaders, RateLimiting...)
├── App/
│   ├── Http/
│   │   ├── Controllers/   Finos: recebem, chamam o service, devolvem view/redirect
│   │   ├── Requests/      Validação do formulário (herdam de Core\Request)
│   │   ├── Middleware/    Auth, Admin, Guest, LoginRateLimiting
│   │   └── Exceptions/    Exceções de domínio com named constructors (UsuarioException::credenciaisInvalidas())
│   ├── Services/          Casos de uso (+ IServices/ com as interfaces)
│   ├── Infrastructure/    E-mail, repositórios (+ IRepositories/ com as interfaces)
│   ├── Models/            Entidades (User, Exemplo)
│   ├── ValueObjects/      Senha
│   ├── Enums/             Perfil, Status, HttpStatus
│   ├── Dto/               Dados de entrada dos casos de uso
│   └── Utils/             View, Url, Toast, templates de e-mail
├── config/dependencies/   Ligações do Container (compartilhado.php + modulos/*.php, carregados por glob)
├── routes/modulos/        Rotas por área (carregadas por glob)
├── views/                 Telas (Admin, Config, Login, Home, Exemplo, Shared)
└── database/base.sql      Única fonte do schema (os testes de integração a reutilizam)
```

Funcionalidade nova não exige editar arquivo central: crie `config/dependencies/modulos/<area>.php` e `routes/modulos/<area>.php`; o `glob` carrega sozinho.

## 3. Como o código flui e o que cada camada pode

```
Router -> Middlewares -> Controller -> Request(valida) -> Service -> Repository(interface) -> Banco
```

| Camada | Pode | Não pode |
|---|---|---|
| Controller | Ler `$_POST`/rota, montar Request/Dto, chamar **um** service, escolher view/redirect | Regra de negócio, SQL, `new` de repositório |
| Service | Orquestrar o caso de uso, abrir `Transaction::run`, lançar exceção de domínio | Conhecer HTTP ou HTML |
| Model / ValueObject | Regras do próprio objeto (`Senha`, `User`) | Conhecer banco ou framework |
| Repository | SQL com `prepare`, mapear linha em Model | Regra de negócio |

Exemplo completo para copiar o estilo: a funcionalidade `Exemplo` (`ExemploController`, `ExemploService`, `ExemploRepository`, `views/Exemplo`, `routes/modulos/exemplo.php`).

Para criar uma funcionalidade: copie `Exemplo`, renomeie e procure os `TODO`; registre no Container **interfaces** (`IXRepository`, `IXService`) e só depois o controller; rotas com `$router->get(...)` e middleware; todo `POST` já passa por `Csrf::check()`.

## 4. SOLID e Object Calisthenics — como aplicar

São critérios de decisão, **não regras mecânicas**. Se aplicar piora a leitura, não aplique. Só crie abstração quando houver uma dor concreta hoje.

| Princípio | Como aparece aqui | Evite |
|---|---|---|
| **S** | Controller só traduz HTTP; regra de senha mora em `Senha` | Controller que valida regra de negócio ou faz SQL |
| **O** | `Services/Auditoria/AuditoriaUser` decora `IUserRepository` e adiciona auditoria sem tocar no `UserRepository` | `if ($auditar)` dentro do repositório |
| **L** | Qualquer `IUserRepository` serve ao `AuthService` (os testes usam `FakeUserRepository`) | Implementação que lança erro onde a interface promete retorno |
| **I** | `IAuthService`, `IRecuperacaoSenhaService`, `IEmailSender` separados | Interface "faz-tudo" |
| **D** | Services recebem `I*Repository` no construtor; a ligação fica em `config/dependencies` | `new UserRepository()` dentro de um service |

- `declare(strict_types=1);` em todo arquivo PHP.
- Validação de formulário em `Http/Requests/*`; regra de negócio no Model/ValueObject/Service.
- Escrita concorrente: coluna `version` + lock otimista (`WHERE version = :esperada`), como em `UserRepository::updateUser`. Várias escritas juntas: `Transaction::run(...)`.
- SQL somente com `prepare`/`bindValue`. Nunca concatene entrada do usuário.
- Senha só existe como `Senha` (hash). Mensagem de login é **genérica** ("Usuário ou senha incorretos").
- Todo `<form method="post">` leva `<?= csrf() ?>`.

## 5. Tema e estilo (trocar cores e fontes sem dor)

Cor, fonte e padrão visual moram em **um** arquivo: `public/css/input.css`. As views só usam nomes por função.

| Quero… | Faço assim |
|---|---|
| Trocar a cor da marca | Edite `--color-primary`, `--color-primary-hover` e `--color-primary-soft` |
| Trocar fundos, bordas, textos, estados | Edite os `--color-*` do bloco `@theme` |
| Trocar a fonte | Edite `--font-sans` / `--font-serif` **e** os `<link>` de fonte em `src/views/Shared/index.php` |
| Mudar todos os botões, cartões ou campos de uma vez | Edite o componente em `@layer components` (`.btn`, `.card`, `.field-input`...) |
| Depois de qualquer mudança | `npm run build` e commite o `public/css/app.css` |

Tokens: marca (`primary`, `primary-hover`, `primary-soft`, `on-primary`, `on-primary-soft`); superfícies (`page`, `surface`, `surface-muted`, `line`, `line-strong`, `focus`); texto (`ink`, `ink-soft`, `muted`, `faint`, `subtle`); estados (`danger*`, `success*`, `warning-*`).

Regras para views **e** JavaScript:

- Nunca a paleta crua (`bg-slate-900`, `text-rose-600`, `bg-white`): use `bg-primary`, `text-danger`, `bg-surface`. Vale também para classes que o JS adiciona (`classList.add`).
- Nunca cor em `style=""` nem `text-[#hex]`. Se faltar uma cor, crie um token em `input.css`.
- Componentes: `page-title`, `card`, `stat-value`, `auth-card` + `auth-title` + `auth-subtitle`, `field-label`, `field-input` (+ `--lg`, `--icon`), `field-error`, `password-field` + `toggle-password`, `code-input`, `btn` + `btn-primary` | `btn-secondary` | `btn-danger` (+ `btn-lg`).
- Todo título de página é `<h1 class="page-title">`.
- Ícones: só Font Awesome (`fa-solid fa-*`). Não adicione outra biblioteca.
- Campo de senha:
  ```html
  <div class="password-field mt-1">
      <input type="password" id="senha" class="field-input">
      <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar senha"><i class="fa-solid fa-eye-slash"></i></button>
  </div>
  ```
  A lógica do olho é **uma só**: `public/js/utils/password-toggle.js` (`iniciarBotoesDeVerSenha()`). Não copie. O ícone mostra o estado atual: oculta = `eye-slash`, visível = `eye`.
- Classe nova só existe no CSS depois do `npm run build`. O Tailwind lê apenas `src/views` e `public/js` (`source(none)` em `input.css`, para o build ser reproduzível).
- O `ViewsDoTemaTest` falha se alguém usar paleta crua, cor inline, outra biblioteca de ícones, esquecer o `aria-label` do olho, ou se o `app.css` estiver desatualizado.

## 6. Segurança

| Área | Como está | Onde |
|---|---|---|
| XSS | `View::render` escapa todo `{{x}}` por padrão, numa única passada. Só `content`, `header`, `footer`, `sidebar`, `toast` são HTML confiável | `App/Utils/View.php` |
| Login | Mensagem genérica; e-mail inexistente gasta o mesmo tempo (hash isca); bloqueio progressivo após 5 falhas (1, 5, 15, 30, 60 min) por IP + e-mail normalizado; sucesso zera; senha certa durante o bloqueio **também** é recusada | `AuthService`, `Core/RateLimiting.php`, `LoginRateLimitingMiddleware` |
| Recuperação de senha | Resposta idêntica para e-mail cadastrado e não cadastrado (nenhuma mensagem de "e-mail não encontrado"); código de 6 dígitos morre após 5 erros; limite por IP + e-mail nos dois passos | `RecuperacaoSenhaService`, `LoginController` |
| Sessão | Cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` em HTTPS), modo estrito, id regenerado no login | `Core/Session.php` |
| Cabeçalhos | CSP, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`; `X-Powered-By` removido. **Sem HSTS de propósito**: com ele o navegador esconde o "continuar mesmo assim" quando o certificado vence | `Core/SecurityHeaders.php` |
| Autorização | Admin não deleta nem desativa a própria conta | `UserService` |
| CSRF | Todo POST; token comparado com `hash_equals` | `Core/Csrf.php` |
| SQL | Só `prepare`/`bindValue` | repositórios |
| Arquivos | `.htaccess` da raiz redireciona tudo para `public/`; `Options -Indexes` em `public/` | `.htaccess` |

Regras:

- HSTS fica desligado: com certificado vencido o navegador ainda mostra "continuar mesmo assim". Para ligar em produção, adicione `Strict-Transport-Security` em `SecurityHeaders::cabecalhos()` (comece com `max-age` curto, ex.: 300) **e** troque o teste `testNaoEnviaHstsParaQueCertificadoVencidoPermitaAbrirMesmoAssim`. Só faça com renovação automática do certificado funcionando.
- Dado do usuário (nome, e-mail, mensagem) **nunca** vira HTML sem escape. Não desligue o escape do `View`.
- Resposta de autenticação ou recuperação **nunca** revela se o e-mail existe: nem no texto, nem no status, nem no redirecionamento, nem no tempo.
- Ação sensível nova usa `RateLimiting::chave('<escopo>', $email)`: `segundosRestantes()` antes, `registrarFalha()` no erro, `limpar()` no sucesso.
- Nova origem externa (CDN, fonte, imagem) exige liberar em `SecurityHeaders` (`ORIGENS_*`). Prefira hospedar o arquivo localmente.
- Ao mexer em `composer.json`, rode `composer audit`.
- Cada regra tem teste (`vendor/bin/phpunit --group seguranca`). Teste novo de segurança deve ser visto **falhando** antes de passar.

## 7. Testes

```
tests/
├── Unit/          Sem banco, sem rede, sem servidor (Unit/App/Services, Unit/Core, Unit/Frontend...)
├── Integration/   MySQL real (banco *_test) e HTTP real (servidor embutido do PHP)
└── Support/       Fixtures e helpers: UserFactory, FakeUserRepository, FakeEmailSender, TestDatabase, HttpClient...
```

| Comando | O que roda |
|---|---|
| `composer test` | unit + integration |
| `composer test:unit` | só unitários (rápidos, rodam em qualquer máquina) |
| `composer test:integration` | MySQL + HTTP (precisa do MySQL do `.env` ligado) |
| `vendor/bin/phpunit --group seguranca` | só os de segurança (rode ao mexer em login, sessão, views, e-mail) |
| `vendor/bin/phpunit --testsuite legacy-src` | teste antigo `tests/LoginTest.php` |

| Mudei… | Escreva |
|---|---|
| Model, ValueObject, Enum | Unitário |
| `Service` | Unitário com repositório em memória (`FakeUserRepository`), cobrindo erro e borda |
| `Repository`/SQL | Integração com `DatabaseTestCase` |
| Rota, middleware, sessão, CSRF, redirect | Integração HTTP com `HttpTestCase` (veja `LoginHttpTest`) |

Regras:

- Toda classe de teste leva `#[CoversClass(...)]` (o `phpunit.xml` exige); testes HTTP usam `#[CoversNothing]`.
- **Integração nunca toca o banco de desenvolvimento.** Usa `<DB_NAME>_test` (criado sozinho; schema vindo de `src/database/base.sql`). O `TestDatabase` aborta qualquer `TRUNCATE` se o banco não terminar em `_test`.
- Sem MySQL, a integração é **pulada** com mensagem. Sem DNS, os testes de e-mail válido também (`Validations::email` consulta o MX).
- O servidor dos testes HTTP roda com `EMAIL_DRIVER=arquivo`: nenhum e-mail real sai. Leia o código enviado com `ultimoCodigoEnviadoPara($email)`.
- Teste novo precisa **falhar quando o código quebra**. Desfaça a correção de propósito e confirme que a suíte fica vermelha.

## 8. Dívidas conhecidas (não replique; corrija com combinação do time)

| Dívida | Onde | Efeito |
|---|---|---|
| CSP com `'unsafe-inline'` | `Core/SecurityHeaders.php`; `<script>` e `style=""` embutidos nas views | Um XSS ainda poderia executar script inline. Próximo passo: mover para arquivos e usar nonce |
| CDNs sem integridade (SRI) | Font Awesome (cdnjs) e Google Fonts em `views/Shared/index.php` | Se o CDN for comprometido, o código chega ao navegador. Hospedar localmente ou usar SRI |
| Limitador só por IP + e-mail | `Core/RateLimiting.php` | Não cobre um IP testando muitos e-mails nem ataque distribuído; atrás de proxy todo mundo compartilha o IP |
| Sem controle próprio de inatividade da sessão | `Core/Session.php` | O prazo depende do `session.gc_maxlifetime` do PHP |
| Tempo de resposta da recuperação ainda difere | `RecuperacaoSenhaService` (e-mail real faz chamada HTTP; inexistente não) | Canal de tempo bem mais fraco que o texto da resposta |
| Logout por `GET` | `routes/modulos/login.php` | Um site externo pode deslogar o usuário |
| `redirectOnFail` usa `HTTP_REFERER` como destino | `Core/Request.php` | Destino vem do cliente; restringir a caminhos do próprio site |
| Dependências de desenvolvimento com avisos | `composer audit`: `phpcs`, `symfony/cache`, `filesystem`, `http-client` | Só dev; atualizar quando as ferramentas liberarem |
| `larapack/dd` em `require` (produção) | `composer.json` | Ferramenta de debug instalada em produção; mover para `require-dev` |
| Validação de e-mail faz consulta DNS | `Core/Validations.php::email` (`checkdnsrr`) | Login e testes dependem de internet |

## 9. Antes de abrir o PR

- [ ] Camadas respeitadas (controller fino, regra no service/model, SQL só no repositório).
- [ ] `composer test` verde (ou o motivo de cada skip é conhecido).
- [ ] Teste novo cobre o caminho feliz **e** pelo menos um erro/borda.
- [ ] POST com `csrf()`, SQL com `prepare`, mensagem de erro sem vazar dado, dado do usuário escapado.
- [ ] Visual: só tokens e componentes do tema (sem paleta crua, sem cor inline) e `npm run build` rodado.
- [ ] Mexi em `composer.json`? Rodei `composer audit`.
- [ ] Nada de commit/push/deploy sem autorização de quem pediu a tarefa.
