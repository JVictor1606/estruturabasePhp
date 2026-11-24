# Guia da base — monolito modular PHP

Leia isto antes de escrever código aqui. Vale para pessoas e para o Claude Code.
Idioma: português brasileiro nos nomes de domínio, mensagens e comentários.

## 1. Duas estruturas, uma é a oficial

| Pasta | O que é | Como rodar |
|---|---|---|
| `modular/` | **Monolito modular** (a versão em evolução, independente do `src/`) | `APP_ESTRUTURA=modular` no `.env` |
| `src/` | MVC em camadas globais (versão original). Padrão quando a variável não existe | sem variável |

- O `public/index.php` escolhe a estrutura. Cada uma carrega os **seus próprios** helpers (`csrf()`, `old()`, `old_error()`, `flash()`), porque as duas definem funções globais com o mesmo nome e não podem ser carregadas juntas.
- Código novo vai em `modular/`. Não coloque nada do `modular/` dependendo do `src/` (nem `use Src\`, nem `require` de `src/`).
- Depois de clonar: `composer install` (gera o autoload, que não vai no Git) e copie `.env.example` para `.env`.
- Variáveis do `.env` que mudam o comportamento: `APP_ESTRUTURA` (modular ou src), `DB_NAME_TEST` (banco dos testes), `EMAIL_DRIVER` (`http` envia de verdade; `arquivo` grava em `EMAIL_ARQUIVO`, use no desenvolvimento para não disparar e-mail real).

## 2. Mapa e dono de cada parte

```
modular/
├── Core/                     Infraestrutura do framework (Router, Container, Database, Csrf, Request, Transaction...)
├── Shared/                   Coisas comuns às telas (SharedController, View, Url, Toast, Enums, DomainException)
└── Modules/<Modulo>/         UM módulo = UMA responsabilidade de negócio
    ├── Domain/               Entidades, Value Objects, Enums, Exceptions e INTERFACES de repositório
    ├── Dto/                  Dados de entrada dos casos de uso (CriarXDto, EditXDto)
    ├── Services/             Casos de uso (I<Nome>Service + implementação)
    ├── Infrastructure/       Banco, e-mail, auditoria (implementam as interfaces do Domain)
    ├── Http/
    │   ├── Controllers/      Finos: recebem, chamam o service, devolvem view/redirect
    │   ├── Requests/         Validação do formulário (herdam de Core\Request)
    │   └── Middleware/
    ├── Views/<Modulo>/       Telas do módulo
    ├── routes.php            Rotas do módulo (carregadas automaticamente)
    └── dependencies.php      Ligações do Container do módulo (carregadas automaticamente)
```

Módulos hoje: `Auth` (login, sessão, recuperação de senha), `Usuarios`, `Auditoria`, `Exemplo` (template), `Home`.

### Regra de ouro: cada pessoa mexe só no módulo pelo qual responde

| Quero mexer em… | Posso sozinho? |
|---|---|
| Qualquer coisa dentro do **meu** módulo | Sim |
| Outro módulo | Não. Peça ao responsável, ou combine a mudança na interface (`I*Service`/`I*Repository`) |
| `Core/` ou `Shared/` | Não é livre: afeta todo mundo. Mudança pequena e retrocompatível, com aviso e revisão |
| `phpunit.xml`, `composer.json`, `public/index.php`, `src/database/base.sql` | Com aviso. São pontos compartilhados |

Um módulo novo **não exige editar arquivo central**: o `bootstrap.php` e o `routes.php` fazem `glob` em `Modules/*/dependencies.php` e `Modules/*/routes.php`.

### Dependências permitidas entre módulos

- Módulo → `Core` e `Shared`: livre.
- Módulo → outro módulo: **só pelo contrato**: interfaces (`I*Service`, `I*Repository`), Exceptions, Enums e Value Objects do `Domain`, e middlewares citados no `routes.php`.
- **Proibido**: usar a classe concreta de `Infrastructure` ou `Services` de outro módulo, ou ler tabela de outro módulo direto com SQL. Quem precisa de dado de outro módulo chama o service dele.
- `Core` e `Shared` **não** dependem de `Modules` (veja as exceções em "Dívidas conhecidas" — não replique).

## 3. Como o código flui

```
Router -> Middlewares -> Controller -> Request(valida) -> Service -> Repository(interface) -> Banco
                              |                              |
                              v                              v
                        View / Url::redirect         Domain (regras) + Exceptions
```

| Camada | Pode | Não pode |
|---|---|---|
| Controller | Ler `$_POST`/rota, montar Request/Dto, chamar **um** service, escolher view/redirect | Regra de negócio, SQL, `new` de repositório |
| Service | Orquestrar o caso de uso, abrir `Transaction::run`, lançar exceção de domínio | Conhecer HTTP, sessão ou HTML |
| Domain | Regras e validações do próprio objeto (`Senha`, `User`) | Conhecer banco ou framework |
| Infrastructure | SQL com `prepare`, e-mail, auditoria | Regra de negócio |

Exemplos reais para copiar o estilo: `Modules/Exemplo/` (módulo completo), `Modules/Usuarios/Domain/Senha.php` (Value Object), `Modules/Auth/Services/AuthService.php` (guard clause + exceção de domínio).

## 4. Criar um módulo novo

1. Copie `Modules/Exemplo` para `Modules/<Nome>` e renomeie namespace, classes e rotas. Procure os `TODO`.
2. `dependencies.php`: registre com `Container::set(IXRepository::class, fn () => ...)`, depois service, depois controller. Dependa de **interfaces**.
3. `routes.php`: `$router->get('/x', [XController::class, 'index'], [AuthMiddleware::class]);`. Todo `POST` já passa por `Csrf::check()`.
4. Views em `Views/<Nome>/...` e chamadas como `View::render('<Nome>/index', [...])`. O nome da view é **global** entre módulos (`glob Modules/*/Views/<nome>.php`): use sempre a pasta com o nome do seu módulo para não colidir.
5. Tabela: acrescente em `src/database/base.sql` (única fonte do schema; os testes de integração a reutilizam).
6. Menu lateral: hoje é fixo em `Shared/Http/Controllers/SharedController.php` + `Shared/Views/Shared/sidebar.php`. É um ponto compartilhado: combine a alteração.
7. Visual: use os componentes e tokens da seção 6 (nunca a paleta crua). Segurança: siga a seção 7.
8. Escreva os testes (seção 8) no mesmo PR.

## 5. SOLID e Object Calisthenics — como aplicar aqui

São critérios de decisão, **não regras mecânicas**. Se aplicar piora a leitura, não aplique. Só crie abstração quando houver uma dor concreta hoje.

| Princípio | Como aparece neste código | Faça / Evite |
|---|---|---|
| **S** (uma razão para mudar) | Controller só traduz HTTP; regra de senha mora em `Senha` | Controller que valida regra de negócio ou faz SQL |
| **O** (estender sem editar) | `Usuarios/Infrastructure/AuditoriaUser` é um *Decorator* de `IUserRepository`: adiciona auditoria sem tocar no `UserRepository` | Enfiar `if ($auditar)` dentro do repositório |
| **L** (substituível) | Qualquer `IUserRepository` serve ao `AuthService` (o teste usa `FakeUserRepository`) | Implementação que lança erro onde a interface promete retorno |
| **I** (interfaces pequenas) | `IAuthService`, `IRecuperacaoSenhaService`, `IEmailSender` separados | Uma interface "faz-tudo" do módulo |
| **D** (depender de abstração) | Services recebem `I*Repository` no construtor; a ligação fica em `dependencies.php` | `new UserRepository()` dentro de um service |

| Object Calisthenics | Onde já ocorre | Cuidado |
|---|---|---|
| Um nível de indentação / sem `else` | Guard clauses em `AuthService::auth` | Não distorça o código só para remover um `else` legível |
| Encapsular primitivos | `Senha`, `Perfil`, `Status` em vez de `string`/`int` soltos | Não crie Value Object para campo sem regra |
| Não encadear `->` em cadeia de getters | Pergunte ao objeto: `$user->verificaSenha($s)` em vez de `$user->getSenha()->hash()` | Exceções legítimas: fluent builders, DTOs e o mapeamento para persistência no `Repository` (que precisa ler o hash) |
| Entidades pequenas | `Senha` (poucos métodos, uma responsabilidade) | `User` já tem ~20 métodos, quase todos getters: está acima do que se quer em entidade nova |
| Sem getters/setters em excesso | Prefira comportamento (`atualizaSenha`, `isAtivo`) | Setter que aceita estado inválido |

Convenções do projeto:

- `declare(strict_types=1);` em todo arquivo PHP.
- Interfaces com prefixo `I` (`IUserRepository`). Exceções de domínio com *named constructors*: `UsuarioException::credenciaisInvalidas()`.
- Validação de formulário em `Http/Requests/*Request` (herda `Core\Request`); regra de negócio no `Domain`.
- Escrita concorrente: coluna `version` + lock otimista (`WHERE version = :esperada`), como em `UserRepository::updateUser`. Várias escritas juntas: `Transaction::run(...)`.
- SQL somente com `prepare`/`bindValue`. Nunca concatene entrada do usuário.
- Senha só existe como `Senha` (hash). Mensagem de login é **genérica** ("Usuário ou senha incorretos") para não revelar se o e-mail existe.
- Todo `<form method="post">` leva `<?= csrf() ?>`.

## 6. Tema e estilo (trocar cores e fontes sem dor)

Cor, fonte e padrão visual moram em **um** arquivo: `public/css/input.css`. As views só usam nomes por função.

| Quero… | Faço assim |
|---|---|
| Trocar a cor da marca (botão, menu ativo, painel do login) | Edite `--color-primary`, `--color-primary-hover` e `--color-primary-soft` (3 linhas) |
| Trocar fundos, bordas, textos, estados | Edite os `--color-*` do bloco `@theme` |
| Trocar a fonte | Edite `--font-sans` / `--font-serif` **e** os `<link>` de fonte em `modular/Shared/Views/Shared/index.php` |
| Mudar todos os botões, cartões ou campos de uma vez | Edite o componente em `@layer components` (`.btn`, `.card`, `.field-input`...) |
| Depois de qualquer mudança | `npm run build` e commite o `public/css/app.css` |

Tokens de cor (todos apontam para uma cor da paleta; o nome diz a **função**):

| Função | Tokens |
|---|---|
| Marca | `primary`, `primary-hover`, `primary-soft`, `on-primary`, `on-primary-soft` |
| Superfícies | `page` (fundo), `surface` (cartão/campo), `surface-muted`, `line` (borda), `line-strong`, `focus` |
| Texto (forte → fraco) | `ink`, `ink-soft`, `muted`, `faint`, `subtle` |
| Estados | `danger`, `danger-strong`, `danger-soft`, `danger-faint`, `danger-line`; o mesmo para `success`; `warning-strong`, `warning-soft` |

Regras para views **e** JavaScript:

- Nunca a paleta crua (`bg-slate-900`, `text-rose-600`, `bg-white`): use `bg-primary`, `text-danger`, `bg-surface`. Vale para classes que o JS adiciona (`classList.add`).
- Nunca cor em `style=""` nem `text-[#hex]`. Se faltar uma cor, crie um token em `input.css`.
- Componentes disponíveis: `page-title`, `card`, `stat-value`, `auth-card` + `auth-title` + `auth-subtitle`, `field-label`, `field-input` (+ `--lg`, `--icon`), `field-error`, `password-field` + `toggle-password`, `code-input`, `btn` + `btn-primary` | `btn-secondary` | `btn-danger` (+ `btn-lg`).
- Todo título de página é `<h1 class="page-title">` (serifa itálica). Telas de login/recuperação usam `auth-card` com o título dentro do cartão.
- Ícones: só Font Awesome (`fa-solid fa-*`). Não adicione outra biblioteca.
- Campo de senha:
  ```html
  <div class="password-field mt-1">
      <input type="password" id="senha" class="field-input">
      <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar senha"><i class="fa-solid fa-eye-slash"></i></button>
  </div>
  ```
  A lógica do olho é **uma só**: `public/js/utils/password-toggle.js` (`iniciarBotoesDeVerSenha()`). Não copie. O ícone mostra o estado atual: oculta = `eye-slash`, visível = `eye`.
- Classe nova que só existe numa view ou JS só entra no CSS depois do `npm run build`. O Tailwind lê apenas `src/views`, `modular/**/Views` e `public/js` (`source(none)` em `input.css`, para o build ser reproduzível).
- O `ViewsDoModularTest` falha se alguém usar paleta crua, cor inline, outra biblioteca de ícones, esquecer o `aria-label` do olho, ou se o `app.css` estiver desatualizado.
- O `src/` **não** foi migrado para o tema; só o `modular/`.

## 7. Segurança

| Área | Como está | Onde |
|---|---|---|
| XSS | `View::render` escapa todo `{{x}}` por padrão, numa única passada. Só `content`, `header`, `footer`, `sidebar`, `toast` são HTML confiável | `Shared/Support/View.php` |
| Login | Mensagem genérica; e-mail inexistente gasta o mesmo tempo (hash isca); bloqueio progressivo após 5 falhas (1, 5, 15, 30, 60 min) por IP + e-mail normalizado; sucesso zera; senha certa durante o bloqueio **também** é recusada | `AuthService`, `Core/RateLimiting.php` |
| Recuperação de senha | Resposta idêntica para e-mail cadastrado e não cadastrado; código de 6 dígitos morre após 5 erros; limite por IP + e-mail nos dois endpoints | `RecuperacaoSenhaService`, `LoginController` |
| Sessão | Cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` em HTTPS), modo estrito, id regenerado no login | `Core/Session.php` |
| Cabeçalhos | CSP, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS em HTTPS; `X-Powered-By` removido | `Core/SecurityHeaders.php` |
| Autorização | Admin não deleta nem desativa a própria conta | `UserService` |
| CSRF | Todo POST; token comparado com `hash_equals` | `Core/Csrf.php` |
| SQL | Só `prepare`/`bindValue`; nome de tabela/coluna validado por regex | repositórios, `Validations` |
| Arquivos | `.htaccess` da raiz esconde `.env`, logs, código; `Options -Indexes` em `public/` | `.htaccess` |

Regras:

- Dado do usuário (nome, e-mail, mensagem) **nunca** vira HTML sem escape. Não desligue o escape do `View`.
- Resposta de autenticação ou recuperação **nunca** revela se o e-mail existe: nem no texto, nem no status, nem no redirecionamento, nem no tempo.
- Ação sensível nova (reenviar código, convite, troca de e-mail) usa `RateLimiting::chave('<escopo>', $email)`: `segundosRestantes()` antes, `registrarFalha()` no erro, `limpar()` no sucesso.
- Nova origem externa (CDN, fonte, imagem) exige liberar em `SecurityHeaders` (`ORIGENS_*`). Prefira hospedar o arquivo localmente.
- Ao mexer em `composer.json`, rode `composer audit`.
- Nenhuma dessas regras vale só no código: cada uma tem teste (`--group seguranca`). Teste novo de segurança deve ser visto **falhando** antes de passar.

## 8. Testes

```
tests/
├── Unit/          Sem banco, sem rede, sem servidor. Espelha modular/ (Unit/Modules/Auth/Services/...)
│   └── Frontend/  Guarda estática das views e do tema (paleta crua, aria-label, CSS desatualizado)
├── Integration/   MySQL real (banco *_test) e HTTP real (servidor embutido do PHP)
└── Support/       Fixtures e helpers: UserFactory, FakeUserRepository, FakeEmailSender, TestDatabase, HttpClient...
```

| Comando | O que roda |
|---|---|
| `composer test` | unit + integration |
| `composer test:unit` | só unitários (rápidos, rodam em qualquer máquina) |
| `composer test:integration` | MySQL + HTTP (precisa do MySQL do `.env` ligado) |
| `vendor/bin/phpunit --group seguranca` | só os testes de segurança (rode ao mexer em login, sessão, views, e-mail) |
| `vendor/bin/phpunit --testsuite legacy-src` | teste antigo da versão `src/` |

Qual teste escrever:

| Mudei… | Escreva |
|---|---|
| Regra de `Domain` (VO, entidade, enum) | Unitário |
| `Service` | Unitário com `FakeUserRepository`-style (repositório em memória), cobrindo erro e borda |
| `Repository`/SQL | Integração com `DatabaseTestCase` |
| Rota, middleware, sessão, CSRF, redirect | Integração HTTP com `HttpTestCase` (veja `LoginHttpTest`) |

Regras:

- Toda classe de teste leva `#[CoversClass(...)]` (o `phpunit.xml` exige metadados de cobertura); testes HTTP usam `#[CoversNothing]`.
- **Integração nunca toca o banco de desenvolvimento.** Usa `<DB_NAME>_test` (criado sozinho; schema vindo de `src/database/base.sql`). O `TestDatabase` aborta qualquer `TRUNCATE` se o banco não terminar em `_test`. Defina `DB_NAME_TEST` no `.env` se quiser outro nome.
- Sem MySQL, a integração é **pulada** com mensagem (não falha). Sem DNS, os testes de e-mail válido também (a validação `Validations::email` consulta o MX).
- Fixtures usam bcrypt de custo baixo (`UserFactory`). O hash real fica em `SenhaTest`.
- Testes HTTP que erram senha ou pedem código registram `limparRateLimitDe($email)` para não deixar contador em `Limiting/appLimiting.json`.
- O servidor dos testes HTTP roda com `EMAIL_DRIVER=arquivo`: nenhum e-mail real sai. Leia o código enviado com `ultimoCodigoEnviadoPara($email)`.
- Teste novo precisa **falhar quando o código quebra**. Se nunca viu o teste vermelho, ele ainda não provou nada. Para provar, desfaça a correção de propósito e confirme que a suíte fica vermelha.

## 9. Dívidas conhecidas (não replique; corrija com combinação do time)

| Dívida | Onde | Efeito |
|---|---|---|
| `Core` e `Shared` conhecem o módulo `Usuarios` | `Core/Auth.php` (usa `User`); `Shared/Http/Controllers/SharedController.php` (usa `Perfil`) | Quebra "Core não depende de Modules"; `Core` não é reutilizável sozinho |
| `Auth` e `Usuarios` dependem um do outro | `Auth` usa `Usuarios\Domain`; `Usuarios` usa middlewares do `Auth` nas rotas | Acoplamento circular; evoluir os dois juntos |
| CSP com `'unsafe-inline'` | `Core/SecurityHeaders.php`; `<script>` e `style=""` embutidos nas views | Um XSS ainda poderia executar script inline. Próximo passo: mover para arquivos e usar nonce |
| CDNs sem integridade (SRI) | Font Awesome (cdnjs) e Google Fonts em `Shared/Views/Shared/index.php` | Se o CDN for comprometido, o código chega ao navegador. Hospedar localmente ou usar SRI |
| Limitador só por IP + e-mail | `Core/RateLimiting.php` | Não cobre um IP testando muitos e-mails (password spraying) nem ataque distribuído; atrás de proxy todo mundo compartilha o IP |
| Sem controle próprio de inatividade da sessão | `Core/Session.php` | O prazo depende do `session.gc_maxlifetime` do PHP (1440 s aqui) e de um coletor probabilístico, não é garantido |
| Tempo de resposta da recuperação ainda difere | `RecuperacaoSenhaService` (e-mail real faz chamada HTTP; inexistente não) | Resta um canal de tempo, bem mais fraco que o texto da resposta |
| Logout por `GET` | `Modules/Auth/routes.php` | Um site externo pode deslogar o usuário (CSRF de logout) |
| `redirectOnFail` usa `HTTP_REFERER` como destino | `Core/Request.php` | Destino vem do cliente; restringir a caminhos do próprio site |
| `User` não sanitiza o nome no construtor | `Modules/Usuarios/Domain/User.php` | Sem risco hoje (a saída é escapada), mas é defesa em profundidade a menos |
| Dependências de desenvolvimento com avisos | `composer audit`: `phpcs`, `symfony/cache`, `filesystem`, `http-client` | Só dev; atualizar quando as ferramentas liberarem |
| `larapack/dd` em `require` (produção) | `composer.json` | Ferramenta de debug instalada em produção; mover para `require-dev` |
| Validação de e-mail faz consulta DNS | `Core/Validations.php::email` (`checkdnsrr`) | Login e testes dependem de internet; sem DNS, e-mail válido é recusado |
| `DocsApiController` sem rota | `Shared/Http/Controllers/api/` | Swagger inacessível nas duas estruturas |
| `modular/` fora da análise estática | `phpstan.neon`, `phpcs.xml`, `psalm.xml`, `phpinsights.php` só apontam para `src/` | Estilo e tipos não são checados no modular |
| `src/` fora do tema e das correções de segurança | `src/views`, `src/Core`, `src/App` | A versão antiga mantém paleta crua, ícones Phosphor, olho caído e as falhas de login/recuperação |
| `public/index.php` ainda tem o ramo `src` | `public/index.php` | Ao aposentar o `src/`, simplifique para só o modular |

## 10. Antes de abrir o PR

- [ ] Mexi só no meu módulo (ou avisei sobre `Core`/`Shared`).
- [ ] Nenhum `use Src\` e nenhum acesso à `Infrastructure` de outro módulo.
- [ ] `composer test` verde (ou o motivo de cada skip é conhecido).
- [ ] Teste novo cobre o caminho feliz **e** pelo menos um erro/borda.
- [ ] POST com `csrf()`, SQL com `prepare`, mensagem de erro sem vazar dado, dado do usuário escapado.
- [ ] Visual: só tokens e componentes do tema (sem paleta crua, sem cor inline) e `npm run build` rodado.
- [ ] Mexi em `composer.json`? Rodei `composer audit`.
- [ ] Nada de commit/push/deploy sem autorização de quem pediu a tarefa.
