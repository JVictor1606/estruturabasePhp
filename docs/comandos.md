# Comandos do projeto (`sgc`)

Ferramentas de linha de comando para três tarefas do dia a dia:

| Tarefa | Comando |
|---|---|
| Criar e acompanhar **migrations** (alterações no banco) | `migration:novo`, `migration:status`, `migration:marcar` |
| Criar uma **funcionalidade nova** já com a estrutura de pastas | `modulo` |
| Gerar a **cópia do SQL para o MariaDB** (servidor) a partir do MySQL (local) | `mariadb:gerar` |

> **O que estes comandos NÃO fazem:** nenhum deles **usa o banco de dados** (não conecta, não lê, não escreve) e nenhum executa o SQL das migrations. Quem roda o SQL é você, no cliente de banco de dados de sua preferência. Os comandos criam arquivos, conferem o que falta e anotam, num **arquivo local**, o que já foi feito.

Para ver o passo a passo de criar um módulo na mão (sem o comando), veja [`como-criar-modulo.md`](como-criar-modulo.md).

---

## 1. Como rodar

Na pasta do projeto (depois de `composer install`). Os comandos não precisam do `.env` nem de banco ligado:

| Forma | Exemplo | Quando usar |
|---|---|---|
| PHP direto | `php bin/sgc.php migration:status` | Funciona em qualquer sistema |
| Composer | `composer sgc -- migration:status` | O `--` separa os argumentos do Composer dos do comando |
| Atalho do Windows | `.\sgc migration:status` | No PowerShell/CMD do Windows. É `.\sgc` (com ponto e barra): o Windows não procura comandos na pasta atual |

Sem argumentos, `php bin/sgc.php` mostra a ajuda:

```
Uso: php bin/sgc.php <comando> [argumentos] [--opcoes]

Comandos:
  migration:status   Mostra quais migrations já foram registradas e quais faltam
  migration:novo     Cria o arquivo de uma migration:  migration:novo <Area> <nome>
  migration:marcar   Registra como aplicada (sem rodar o SQL):  migration:marcar <arquivo>|--todas --sim
  modulo             Cria uma funcionalidade nova a partir do Exemplo:  modulo <Nome> [--com-tabela] [--dry-run]
  mariadb:gerar      Gera a cópia MariaDB do base.sql e das migrations:  mariadb:gerar [arquivo] [--saida=pasta]
```

**Atalho `sgc` de qualquer pasta (opcional, uma vez por pessoa):** coloque a pasta do projeto no `PATH` do Windows, ou crie uma função no perfil do PowerShell:

```powershell
function sgc { php C:\caminho\do\projeto\bin\sgc.php @args }
```

**Códigos de saída:** `0` = deu certo; `1` = erro de uso **ou** há algo a corrigir (por exemplo, `migration:status` com migration pendente). Útil em scripts.

### O que cada comando mexe

| Comando | Usa o banco? | O que escreve |
|---|---|---|
| `migration:status` | Não | Nada (só lê o registro local) |
| `migration:novo` | Não | **1** arquivo `.sql` |
| `migration:marcar` | Não | O registro local `src/database/.migrations-aplicadas.json`, e só com `--sim` |
| `modulo` | Não | Os arquivos do módulo (nunca sobrescreve) |
| `mariadb:gerar` | Não | Cópias em `src/database/mariadb/` (os originais não mudam) |

---

## 2. Migrations

Uma **migration** é um arquivo `.sql` com **uma** alteração no banco (criar tabela, adicionar coluna...). Os arquivos ficam em `src/database/Migrations/<Area>/` e um **registro local** (o arquivo `src/database/.migrations-aplicadas.json`) guarda quais já foram aplicadas. Esse registro é **do ambiente**: cada máquina (a sua, a de outra pessoa, o servidor) tem o seu, e ele **não vai para o Git**.

### Como os arquivos são nomeados

```
src/database/Migrations/
└── Exemplo/                                  ← pasta da funcionalidade (Area)
    └── 20261125_103500_novos_campos.sql      ← data_hora_nome
```

- A **data e a hora** no nome definem a ordem (vale a ordem global, não a da pasta).
- Duas pessoas criando migrations no mesmo dia não colidem, porque a hora é diferente.
- Arquivos que não terminam em `.sql` são ignorados.

### Fluxo completo (passo a passo)

Exemplo: adicionar a coluna `categoria` em `tb_exemplo`.

1. **Criar o arquivo**

   ```
   php bin/sgc.php migration:novo Exemplo novos_campos
   ```
   ```
   Migration criada: Exemplo/20261125_103500_novos_campos.sql
   Escreva o SQL no arquivo, aplique no banco e depois registre com:
     php bin/sgc.php migration:marcar Exemplo/20261125_103500_novos_campos.sql --sim
   ```

2. **Escrever o SQL** no arquivo (abaixo do cabeçalho de comentários):

   ```sql
   ALTER TABLE tb_exemplo
     ADD COLUMN categoria varchar(60) NULL AFTER descricao;
   ```

3. **Atualizar o `src/database/base.sql`** com a mesma mudança. O `base.sql` é a instalação do zero e os testes de integração o usam; se ficar diferente das migrations, um banco novo e um banco antigo ficam diferentes.

4. **Aplicar o SQL no banco** (local primeiro). Os comandos `sgc` não fazem isso: rode o conteúdo do arquivo no seu cliente de banco.

5. **Registrar que foi aplicada**

   ```
   php bin/sgc.php migration:marcar Exemplo/20261125_103500_novos_campos.sql --sim
   ```

6. **Conferir**

   ```
   php bin/sgc.php migration:status
   ```

7. No **servidor**, repita os passos 4 a 6 (rodando o SQL no MariaDB, veja a seção 4). O registro do servidor é separado do seu: lá você também usa o `migration:marcar`.

### `migration:novo <Area> <nome>`

| Parte | Regra | Exemplos |
|---|---|---|
| `<Area>` | Letras e números, começando por letra. Normalmente o nome da funcionalidade | `Exemplo`, `Usuarios` |
| `<nome>` | Minúsculas, números e `_`. Espaços e `-` viram `_`. Mínimo 2 letras | `novos_campos`, `criar_tabela_produto` |

- Nunca sobrescreve um arquivo existente.
- O arquivo já nasce com um cabeçalho de comentários com os cuidados de MySQL e MariaDB (seção "Regras de ouro"). Enquanto só tiver comentários, o `migration:status` o mostra como **vazio**.

### `migration:status`

Mostra cada arquivo e em que situação está. Só lê o registro local: não usa o banco e não escreve nada.

```
Registro: arquivo C:/.../src/database/.migrations-aplicadas.json
  [OK]       Exemplo/20261120_090000_criar_tabela.sql   aplicada em 2026-11-20 09:10:00
  [FALTA]    Exemplo/20261125_103500_novos_campos.sql   pendente
  [FALTA]    Exemplo/001_antiga.sql   pendente (arquivo vazio: ainda sem SQL)

Resumo: 1 aplicada, 2 pendentes
```

*(exemplo ilustrativo)*

| Marca | Significa | O que fazer |
|---|---|---|
| `[OK]` | Está no registro local e o arquivo não mudou | Nada |
| `[FALTA]` | Existe o arquivo, mas não está registrada | Aplicar o SQL e usar `migration:marcar` |
| `[FALTA] ... (arquivo vazio)` | O arquivo só tem comentários | Escrever o SQL |
| `[ALTERADA]` | O arquivo foi **editado depois** de registrado (o checksum mudou) | Reverter a edição e criar uma migration nova com a mudança |
| `[SUMIU]` | Está no registro local, mas o arquivo não existe mais | Recuperar o arquivo (Git) |

O código de saída é `1` se houver qualquer marca diferente de `[OK]`.

> O checksum ignora o tipo de quebra de linha (Windows `CRLF` x Linux `LF`) e o BOM. Abrir o arquivo em editores diferentes não gera `[ALTERADA]`.

### `migration:marcar <arquivo> --sim` e `--todas`

Anota no registro local que a migration **já foi aplicada**. **Não roda o SQL e não usa o banco.**

| Uso | O que faz |
|---|---|
| `migration:marcar <arquivo>` (sem `--sim`) | **Só mostra** o que registraria e onde. Nada é gravado. Sai com código `1` |
| `migration:marcar <arquivo> --sim` | Registra de fato |
| `migration:marcar --todas --sim` | Registra todas as pendentes que **têm SQL** (as vazias são puladas). Útil para "baseline": ambiente que já está em dia e ainda não tem registro |

Aceita o caminho relativo à pasta de migrations (`Exemplo/2026..._x.sql`) ou o completo (`src/database/Migrations/Exemplo/2026..._x.sql`).

Recusa: arquivo que não existe, arquivo vazio, e migration já registrada (nesse caso apenas avisa).

O arquivo `src/database/.migrations-aplicadas.json` é criado na primeira vez que se registra algo. Um trecho dele:

```json
{
    "Exemplo/20261125_103500_novos_campos.sql": {
        "checksum": "9f2c...",
        "executada_em": "2026-11-25 10:40:12"
    }
}
```

| Campo | Conteúdo |
|---|---|
| (chave) | Caminho da migration |
| `checksum` | Impressão digital do conteúdo, para detectar edição |
| `executada_em` | Data e hora do registro |

É um arquivo de texto: dá para abrir e conferir. Ele está no `.gitignore` (é estado do ambiente, não código).

### Regras de ouro

1. **Uma ideia por arquivo.** Migration pequena é fácil de conferir e de reparar.
2. **Nunca edite uma migration já aplicada.** Crie outra. O `migration:status` avisa com `[ALTERADA]`.
3. **O MySQL não desfaz `ALTER TABLE`/`CREATE TABLE`.** Esses comandos gravam na hora; se um arquivo com vários comandos falha no meio, o que já rodou fica. Por isso: arquivos pequenos e sem `rollback` automático. Para corrigir, escreva uma migration "para frente".
4. **Local é MySQL e o servidor é MariaDB.** Escreva SQL que sirva aos dois:
   - **Não use** `ADD COLUMN IF NOT EXISTS`: o MySQL não aceita (o MariaDB aceita).
   - Prefira a collation **`utf8mb4_unicode_ci`**. As `utf8mb4_0900_*` só existem no MySQL 8 (ou use o `mariadb:gerar`, seção 4).
5. **Coluna nova em tabela que já tem linhas:** `NULL` ou `DEFAULT`, senão o `ALTER` falha.
6. **Mantenha o `base.sql` igual** ao resultado das migrations.

---

## 3. Criar uma funcionalidade (`modulo`)

```
php bin/sgc.php modulo <Nome> [--com-tabela] [--dry-run]
```

Também aceita `php bin/sgc.php --modulo <Nome>`.

O comando **copia a funcionalidade `Exemplo`** trocando os nomes. O módulo nasce como um CRUD funcionando (campos `titulo` e `descricao`), no mesmo padrão do código real.

### Nome

- **PascalCase**, só letras e números: `Produto`, `ControleDeEstoque`, `Pedido2`.
- Recusa: minúsculas (`produto`), hífen, espaço, `../`, nome começando por número, sigla toda em maiúscula (`PDF`) e `Exemplo` (é o modelo).
- Em arquivos, URLs e tabela o nome vira minúsculo com `_` entre as palavras: `ControleDeEstoque` → `controle_de_estoque` (`/controle_de_estoque`, `tb_controle_de_estoque`).

### O que é criado (exemplo: `modulo Produto --com-tabela`)

```
src/App/Dto/Produto/CriarProdutoDto.php
src/App/Dto/Produto/EditProdutoDto.php
src/App/Http/Controllers/ProdutoController.php
src/App/Http/Exceptions/Produto/ProdutoException.php
src/App/Http/Requests/ProdutoRequest.php
src/App/Infrastructure/IRepositories/IProdutoRepository.php
src/App/Infrastructure/Repositories/ProdutoRepository.php
src/App/Models/Produto.php
src/App/Services/Auditoria/AuditoriaProduto.php
src/App/Services/ProdutoService.php
src/App/Services/IServices/IProdutoService.php
src/config/dependencies/modulos/produto.php
src/routes/modulos/produto.php
src/views/Produto/criar.php
src/views/Produto/editar.php
src/views/Produto/index.php
src/database/Migrations/Produto/20261125_110308_criar_tabela_produto.sql   ← só com --com-tabela
```

As rotas e as dependências entram **sozinhas** (o sistema carrega `routes/modulos/*.php` e `config/dependencies/modulos/*.php`). Não precisa editar nenhum arquivo central.

### Opções

| Opção | Efeito |
|---|---|
| `--dry-run` (ou `--simular`) | Mostra o que criaria e **não cria nada** |
| `--com-tabela` | Cria também a migration `criar_tabela_<nome>` com as colunas do modelo (`id`, `titulo`, `descricao`, `status`, `version`, `created_at`, `updated_at`), só com recursos que MySQL e MariaDB aceitam |

### Segurança do comando

- **Nunca sobrescreve.** Se qualquer arquivo de destino já existir, o comando **não cria nada** e lista os conflitos (por exemplo, `modulo Home` é recusado porque `HomeController` já existe).
- Valida o nome antes de tocar em qualquer arquivo.

### Depois de gerar (o comando avisa)

1. **Menu lateral** (ponto compartilhado, não é automático): `views/Shared/sidebar.php` e `SharedController::getSidebar`.
2. **Campos:** troque `titulo` e `descricao` pelos do seu domínio (procure os `TODO` nos arquivos).
3. **Tabela:** sem `--com-tabela`, crie a migration (`migration:novo`). Depois, aplique e registre (seção 2) e atualize o `base.sql`.
4. **Testes:** escreva os do módulo (veja o `CLAUDE.md`, seções 7 e 9).

---

## 4. Cópia para o MariaDB (`mariadb:gerar`)

O banco local é MySQL e o do servidor é MariaDB. Alguns recursos do MySQL 8 o MariaDB não conhece, por exemplo as collations `utf8mb4_0900_*`. Este comando gera uma **cópia** dos `.sql` já adaptada. **Os originais nunca são alterados.**

```
php bin/sgc.php mariadb:gerar [arquivo] [--saida=pasta]
```

| Uso | O que faz |
|---|---|
| `mariadb:gerar` | Converte `src/database/base.sql` e todas as migrations |
| `mariadb:gerar src/database/Migrations/Exemplo/2026..._x.sql` | Converte só esse arquivo |
| `--saida=outra/pasta` | Escolhe a pasta de saída (padrão: `src/database/mariadb`) |

Exemplo de saída:

```
src/database/base.sql  →  src/database/mariadb/base.sql
    1× utf8mb4_0900_ai_ci → utf8mb4_unicode_ci
    ATENÇÃO: Contém DROP TABLE: rodar este arquivo APAGA as tabelas que já existem.
src/database/Migrations/Exemplo/001_novos_campos_table_exemplo.sql  →  src/database/mariadb/Migrations/Exemplo/001_novos_campos_table_exemplo.sql
    sem mudanças (já era compatível)

2 arquivo(s) gerado(s) em src/database/mariadb. Os originais não foram alterados.
Revise a cópia antes de rodar no MariaDB do servidor.
```

### O que é convertido

Só o que vem depois da palavra `COLLATE` (um texto de dado que apenas cite o nome de uma collation não é alterado):

| No MySQL 8 | Vira no MariaDB | Aviso |
|---|---|---|
| `utf8mb4_0900_ai_ci` | `utf8mb4_unicode_ci` | — |
| `utf8mb4_0900_bin` | `utf8mb4_bin` | — |
| `utf8mb4_0900_as_ci` | `utf8mb4_unicode_ci` | **Aproximação**: confira a ordenação e as comparações |
| `utf8mb4_0900_as_cs` | `utf8mb4_bin` | **Aproximação**: confira a ordenação e as comparações |

Também **avisa** (não altera) quando o arquivo contém `DROP TABLE` ou `DROP DATABASE`.

Cada cópia começa com um cabeçalho dizendo que foi gerada e **não deve ser editada**: altere o original e gere de novo. A pasta `src/database/mariadb/` está no `.gitignore` (é derivada, não versionada).

### Usando no servidor

1. `php bin/sgc.php mariadb:gerar`
2. Abra a cópia em `src/database/mariadb/` e leia os `ATENÇÃO`.
3. **Teste primeiro num banco descartável do MariaDB.**
4. Rode a cópia no servidor e, lá, registre a migration com `migration:marcar` (o registro do servidor é um arquivo no próprio servidor).

> O `base.sql` tem `DROP TABLE`: rodá-lo no servidor **apaga os dados**. Use-o só em instalação do zero. Em banco com dados, use as migrations.

---

## 5. Receitas rápidas

**Alterar uma tabela que já existe**
```
php bin/sgc.php migration:novo Usuarios adicionar_telefone
# escrever o ALTER TABLE no arquivo criado; atualizar o base.sql
# rodar o SQL no banco local
php bin/sgc.php migration:marcar Usuarios/<arquivo>.sql --sim
php bin/sgc.php migration:status
```

**Criar uma funcionalidade nova com tabela**
```
php bin/sgc.php modulo Produto --dry-run        # só olhar
php bin/sgc.php modulo Produto --com-tabela     # criar de verdade
# aplicar a migration criar_tabela_produto, registrar com migration:marcar
# ajustar os campos, o menu lateral e escrever os testes
```

**Levar uma mudança de banco para o servidor (MariaDB)**
```
php bin/sgc.php mariadb:gerar
# abrir src/database/mariadb/Migrations/<Area>/<arquivo>.sql, conferir os ATENÇÃO
# rodar no MariaDB do servidor e, lá, migration:marcar <arquivo> --sim
```

---

## 6. Problemas comuns

| Mensagem ou sintoma | Causa | Solução |
|---|---|---|
| `sgc não é reconhecido como um comando` | O Windows não procura na pasta atual | Use `.\sgc ...`, ou `php bin/sgc.php ...` |
| `Comando desconhecido: migrar:status` | O comando se chama `migration:status` | Use `migration:`, não `migrar:` |
| `O registro de migrations está corrompido` | O arquivo `.migrations-aplicadas.json` foi editado à mão e ficou inválido | Corrija o JSON ou apague o arquivo e registre de novo (`migration:marcar --todas --sim`) |
| `[ALTERADA]` | Migration editada depois de registrada | Reverta a edição e crie outra migration |
| `A migration ... está vazia` | O arquivo só tem comentários | Escreva o SQL antes de registrar |
| `O módulo não foi criado porque estes arquivos já existem` | Já existe algo com esse nome | Escolha outro nome ou remova o antigo |
| Registrei a migration errada | Não há comando para "desmarcar" | Abra `src/database/.migrations-aplicadas.json` e apague a entrada dela |
| Acentos estranhos no terminal | Página de código do CMD antigo | No CMD, rode `chcp 65001`. O PowerShell costuma exibir certo |

---

## 7. Para quem mantém os comandos

```
bin/sgc.php                   ponto de entrada (liga cada comando ao seu nome)
src/Console/Aplicacao.php     lê a linha de comando e chama o comando
src/Console/Comando/          um arquivo por comando (implementa IComando)
src/Console/Migracoes/        catálogo, análise, registro em arquivo e gerador de migrations
src/Console/Modulos/          gerador de módulos (copia o Exemplo)
src/Console/Mariadb/          conversor MySQL → MariaDB
```

**Adicionar um comando novo:** crie a classe em `src/Console/Comando/` implementando `IComando` (`descricao()` e `executar()`), registre-a em `bin/sgc.php` e escreva o teste em `tests/Unit/Console/`.

**Mexeu no `Exemplo`?** O gerador de módulos copia os arquivos listados em `GeradorDeModulo::ARQUIVOS_DO_MODELO`. Se você criar ou remover um arquivo "Exemplo", o `GeradorDeModuloTest` falha avisando para atualizar essa lista.

**Testes dos comandos**

```
composer test:unit          # tests/Unit/Console (rápidos, sem banco)
```

Os comandos não usam o banco; por isso todos os testes deles são unitários.

---

## 8. Limites conhecidos

- Nenhum comando **aplica** o SQL nem usa o banco: é você quem roda o SQL. `migration:status` só sabe o que foi **registrado** com `migration:marcar` **nesta máquina** (o registro é local e não é compartilhado entre ambientes).
- Se um dia for útil ter o registro no próprio banco (compartilhado entre ambientes), dá para trocar a implementação de `IRegistroDeMigracoes` sem mudar os comandos. Hoje, de propósito, nada escreve no banco.
- A conversão para MariaDB trata **collations**. Outras diferenças entre MySQL e MariaDB não são convertidas automaticamente; o comando avisa só do `DROP`. Teste a cópia num MariaDB descartável antes de usar.
- A conversão foi testada com testes automáticos e no `base.sql` real, mas **não em um MariaDB de verdade**.
- Não existe comando para desfazer uma migration (de propósito): o MySQL não desfaz alterações de estrutura. Corrija sempre "para frente", com uma migration nova.
