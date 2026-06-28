<p align="center"><img width="200" src="logo.png"></p>

<p align="center">
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://img.shields.io/packagist/dt/tjdft/laravel?cacheSeconds=60">
    </a>
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://img.shields.io/packagist/v/tjdft/laravel?label=stable&color=blue&cacheSeconds=60">
    </a>
    <a href="https://codecov.io/gh/tjdft/laravel" > 
        <img src="https://codecov.io/gh/tjdft/laravel/graph/badge.svg?token=XQ797WDEPA"/> 
    </a>
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://poser.pugx.org/tjdft/laravel/license.svg">
    </a>
</p>

# Introdução

Pacote unificado para desenvolvimento de aplicações Laravel no TJDFT.

**Integrações:**

- Keycloak
- API RH
- Sentry
- SMAX

**Funcionalidades:**

- Auditoria
- Permissões
- Impersonate
- GraphQL Faker

**UI:**

- Componentes **maryUI** + pacotes de ícones.
- Página de **erro** padronizada.
- Arquivos de **translation** em `pt_BR`.

**Utils:**

- Validações adicionais para formatos brasileiros (CPF, CNPJ, CEP, etc).
- Utilitários `Numero` e `Data` para formatações diversas.
- Trait `HasSearchAny` para busca simplificada em **múltiplos campos**.
- Trait `WithPaginationAndReset` para paginação simplificada com **Livewire**.
- Trait `HasSpinnerPlaceholder` para exibir um spinner em componentes `lazy`.
- Trait `TestUtils` com métodos auxiliares para testes automatizados.
- Ativa extensões úteis do **PostgreSQL** (unaccent, pg_trgm, ...).

<br>

# Instalação

Adicione o pacote.

```shell
composer require tjdft/laravel
```

Instale maryUI incluído no pacote.

```shell
php artisan mary:install --yarn
```

Altere o idioma em `.env`

```dotenv
APP_LOCALE=pt_BR
```

Ajuste  `tests/Pest.php`.

```php

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit');
```

Adicione este trecho ao final de `resources/css/app.css`.

```css
/********************************************
* Importa tema do pacote `tjdft/laravel`
*********************************************/

@import "../../vendor/tjdft/laravel/resources/css/tjdft.css";
```

Ajuste `bootstrap/app.php`.

```php
use TJDFT\Laravel\Exceptions\ExceptionHandler;
// ...

->withMiddleware(function (Middleware $middleware) {
    // Para proxy reverso (Openshift)
    $middleware->trustProxies(at: '*');
})
->withExceptions(function (Exceptions $exceptions) {
    // Tratamento personalizado de exceções
    ExceptionHandler::register($exceptions);
})
```

Ajuste  `app/Models/User.php`.

```php
use TJDFT\Laravel\Traits\HasGrant;
use TJDFT\Laravel\Traits\HasImpersonate;
use TJDFT\Laravel\Traits\HasSearchAny;
use Illuminate\Database\Eloquent\Casts\Attribute;
// ...

class User extends Authenticatable
{
    use HasGrant, HasImpersonate, HasSearchAny;
    
    //...
    
    protected $guarded = ['id'];
    
    //...
    
    public function primeiroNome(): Attribute
    {
        return Attribute::make(
            get: fn() => str($this->nome)->explode(' ')->first(),
        );
    }
    
    protected function casts(): array
    {
        return [
            'localizacao' => 'object'
        ];
    }
}
```

Ajuste  `tests/TestCase.php`.

```php
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use TJDFT\Laravel\Traits\TestUtils;

abstract class TestCase extends BaseTestCase
{
    // Helpers para testes automatizados
    use TestUtils;
    
    // Equivale ao `setUp()` do PHPUnit
    protected function boot(): void
    {
        // Adicione aqui qualquer coisa que precise ser executada antes dos testes.
    }    
}
```

Crie as novas variáveis de ambiente em `.env`.

```dotenv
# Sentry
TJDFT_SENTRY_LARAVEL_DSN=

# Fotos
TJDFT_FOTOS_URL=https://<URL_FOTOS>

# Schema onde devem ser ativadas as extensões do PostgreSQL
# Use apenas se o schema principal da aplicação for diferente de `public`.
TJDFT_PGSQL_EXTENSIONS_SCHEMA=core

# API RH
TJDFT_POLVO_API_URL=https://<URL_API_RH>/graphql
TJDFT_POLVO_AUTH_URL=https://<URL_KEYCLOAK>/auth/realms/<NOME_REALM>/protocol/openid-connect/token
TJDFT_POLVO_CLIENT_ID=<NOME_CLIENT>
TJDFT_POLVO_CLIENT_SECRET=<SEGREDO>
TJDFT_POLVO_CACHE_TTL='1 hour'

# Keycloak
TJDFT_KEYCLOAK_BASE_URL=https://<URL_KEYCLOAK>/auth
TJDFT_KEYCLOAK_REALMS=<NOME_REALM>
TJDFT_KEYCLOAK_CLIENT_ID=<NOME_CLIENT>
TJDFT_KEYCLOAK_CLIENT_SECRET=<SEGREDO>

# SMAX
TJDFT_SMAX_URL=https://<URL_SMAX>
TJDFT_SMAX_TENANT_ID=<ID_TENANT_SMAX>
TJDFT_SMAX_REQUESTS_OFFERING=<ID_OFERTA_SMAX>
TJDFT_SMAX_LOGIN=
TJDFT_SMAX_PASSWORD=
TJDFT_SMAX_FALLBACK_EMAILS=
```

Ajuste a migration existente `users`.

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid()->index()->nullable();
    $table->string('login')->index();
    $table->string('matricula')->nullable();
    $table->string('cpf')->index()->nullable();
    $table->string('nome');
    $table->string('email')->nullable();
    $table->string('foto')->nullable();
    $table->json('localizacao')->nullable();
    $table->string('rh_tipo')->nullable();
    $table->string('rh_status')->nullable();
    $table->timestamps();

    $table->unique(['cpf', 'matricula']);
});
```

Rode as migrations.

```shell
# Esta ação destruirá e recriará o banco!

php artisan migrate:fresh --seed
```

**Pronto!**



---

🚨 Configurações padrões ativadas por este pacote.

```php
// Proíbe comandos destrutivos em produção
DB::prohibitDestructiveCommands($this->app->isProduction());

// Moeda
Number::useLocale('pt-BR');
Number::useCurrency('BRL');

// now() + json_encode()
Date::serializeUsing(function ($date) {
    return $date->format('Y-m-d H:i:s');
});
```

<br>

# Autenticação

Este pacote implementa o fluxo de autenticação OAuth2 via `Keycloak` para as rotas protegidas do sistema.

```php
// Rotas protegidas 
Route::middleware('auth')->group(function () {   
    
    Route::livewire('/paginas/create', 'pages::paginas.criar');
    
    // ...
});
```

Para o **logout** de usuários utilize a rota `/auth/logout/keycloak`.


<!-- @formatter:off -->
```html
<x-button title="Sair" link="/auth/logout/keycloak" no-wire-navigate />
```
<!-- @formatter:on -->

Usuários com **mais de um vínculo no RH** serão redirecionados automaticamente para a rota `/auth/perfil` para seleção do perfil de acesso.

```html
<!-- Opção em menu para alternar o perfil -->

<x-menu-item title="Alterar perfil" link="/auth/perfil" />
```

<br>

# Autorização

Utilize a rota `/auth/permissions` para acessar o gerenciamento de permissões.
<!-- @formatter:off -->
```html
<x-menu-item title="Permissões" link="/auth/permissions" :hidden="auth()->user()->cannot('permissoes.gerenciar')" />
```
<!-- @formatter:on -->

Estas são as roles e permissions iniciais registradas automaticamente pelo pacote.

```php
// Permissão master
Permission::create([
    'name' => 'permissoes.gerenciar',
    'description' => 'Permissões / Gerenciar',
]);

// Permissão de impersonate
Permission::create([
    'name' => 'impersonate',
    'description' => 'Impersonate',
]);

// Role admin
Role::create([
    'name' => 'admin',
    'description' => 'Administrador'
])->givePermissionTo(['permissoes.gerenciar', 'impersonate']);
```

**EXEMPLO: `authorize()`**

```php
public function mount(): void 
{
    // Lança uma exceção 403 se o usuário não tiver a permissão
    auth()->user()->authorize("comprovante-rendimentos.visualizar");
}
```

**EXEMPLO: `can()`**

```php
// Se tem a permissão, mostra o aviso
@if(auth()->user()->can('consignacao.portabilidade')) 
    <div>Disponível para portabilidade</div>
@endif
```

**EXEMPLO: `cannot()`**

```html
<!-- Se não tem a permissão, oculta o menu -->

<x-menu-item title="Criar Página" link="/paginas/create" :hidden="auth()->user()->cannot('paginas.criar')" />
```

**EXEMPLO:** crie outras roles e permissions na sua aplicação.

```php
// database/seeders/PermissionsSeeder.php

use Illuminate\Database\Seeder;
use TJDFT\Laravel\Models\Permission;
use TJDFT\Laravel\Models\Role;

// ...

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {     
        if (Permission::where('name', 'comprovante.processar')->count()) {
            return;
        }
        
        // Processar comprovantes
        Permission::create([
            'name' => 'comprovante.processar',
            'description' => 'Comprovantes de Rendimentos / Processar',
        ]);

        // Visualizar comprovantes
        Permission::create([
            'name' => 'comprovante.visualizar',
            'description' => 'Comprovantes de Rendimentos / Visualizar',
        ]);
        
        // FUNCIONÁRIO tem permissão apenas para visualizar
        Role::create([
            'name' => 'funcionario', 
            'description' => 'Funcionário'
         ])->givePermissionTo([
            'comprovante.visualizar',
        ]);
        
        // ADMIN tem todas as permissões
        // A role `admin` já é criada automaticamente pelo pacote
        Role::firstWhere('name', 'admin')->givePermissionTo(Permission::all());     
        
        // Defina os administradores iniciais do sistema
        User::create([
            'cpf' => '0123456789',
            'matricula' => '123456',
            'login' => 't123456',
            'nome' => 'Maria Silva'
        ])->assignRole('admin');
        
        // Note que é inviável atribuir previamente as roles para milhares de `funcionários`.        
        // Confira o exemplo de roles/permissions dinâmicas abaixo.
    }
}
```

**EXEMPLO:** lógica personalizada para definir dinamicamente roles/permissions.

```php
// app/Actions/AtualizarPermissionsLoginAction.php

<?php

namespace App\Actions;

use App\Models\User;

/**
 *  Esta classe é chamada automaticamente pelo pacote `tjdft/laravel` após o login do usuário.
 *  Baseado nos dados do usuário, defina uma lógica para atribuição de roles.
 */
class AtualizarPermissionsLoginAction
{
    public function __construct(private User $user)
    {
    }

    public function execute(): void
    { 
        // Exemplo: se é um `SERVIDOR`, atribua a role 'funcionario'.
        
        if ($this->user->rh_tipo === 'SERVIDOR') {
            $this->user->assignRole('funcionario');
        }
    }
}
```

Adicione `PermissioSeeder` aos seeders da aplicação.

```php
// database/seeders/DatabaseSeeder

class DatabaseSeeder extends Seeder
{   
    public function run(): void
    {
        $this->call([
            // ...
            
            PermissionsSeeder::class,
        ]);
    }
}
```

Rode as migrations.

```shell
# Esta ação destruirá e recriará o banco!

php artisan migrate:fresh --seed
```

<br>

# Fotos

Rota interna para recuperar fotos dos usuários.

```html
<!-- Retorna: https://<URL-FOTOS>/123456.jpg -->

<img src="/tjdft/fotos/12345" />
```

<br>

# Sentry

Integração com o **SENTRY** para monitoramento de erros.

```dotenv
# URL obtida no SENTRY ao configurar o novo projeto.
# Em localhost mantenha vazio, de outra forma os erros não serão reportados ao Sentry.

TJDFT_SENTRY_LARAVEL_DSN=
```

<br>

# SMAX

Integração com o **SMAX** (Central de Atendimento).

```php
// Texto
$conteudo = 'Não consigo acessar a página de comprovantes.';

// A url da página que usuário está visualizando no momento.
$url = request()->header('Referer') ?? url()->previous(),


// Cria a requisição
new SmaxService()->criarRequisicao($conteudo, $url);
```

Em caso de falha o conteúdo será enviado os destinatários configurados.

```dotenv
# Separado por vírgula

TJDFT_SMAX_FALLBACK_EMAILS=
```

<br>

# API RH

Crie serviços de consulta baseados na classe `PolvoService`.

```php
namespace App\Services;

use Illuminate\Support\Collection;
use TJDFT\Laravel\Services\PolvoService;


class FeriasPolvoService extends PolvoService
{
     public function porMatricula(string $matricula): Collection
     {
        $query = "{ ... query GraphQL ... }";
        
        // Método herdado da classe PolvoService
        $response = $this->graphql($query);

        return collect($response['data']['servidor']['dadosFuncionais']['ferias']['data'] ?? []);
     } 
}
```

Todas as consultas GraphQL tem um prazo de cache padrão de **1 hora**.

```dotenv
TJDFT_POLVO_CACHE_TTL='1 hour'.   
```

Pra definir um prazo específico apenas para algumas consultas, utilize o método `lembrar()`.

```php
$ferias = new FeriasPolvoService()->lembrar('1 day')->porMatricula("12345");
```

Para desabilitar o cache em consultas específicas, utilize o método `semCache()`.

```php
$ferias = new FeriasPolvoService()->semCache()->porMatricula("12345");
```

Para desabilitar completamente o cache tem todas as consultas GraphQL ajuste a variável de ambiente.

```dotenv
TJDFT_POLVO_CACHE_TTL='0'
```

<br>

# Impersonate

Utilize a rota `/auth/impersonate` para a funcionalidade de **personificação** de usuários.

<!-- @formatter:off -->
```html
<x-menu-item title="Personificar" link="/auth/impersonate" :hidden="auth()->user()->cannot('impersonate')" />
```
<!-- @formatter:on -->

Adicione no arquivo de layout o aviso de personificação, quando em uso.

```html
<!-- resources/views/layouts/app.blade.php -->

<body>
    <!-- Aviso de Impersonate -->
    <livewire:tjdft::impersonating />

    <div>
        Menu superior ...
    </div>

    <div>
        Conteúdo da página ...
    </div>
</body>
```

<br>

# Pesquisa

Adicione o trait `HasSearchAny` nos models pesquisáveis.

```php
use TJDFT\Laravel\Traits\HasSearchAny;
// ...

class Rubrica extends Model
{
    use HasSearchAny;
    
    //...
}
```

```php
// Pesquisa em múltiplos campos, tratando acentuação e case sensitive automaticamente.
Rubrica::query()->searchAny(['nome', 'sigla'], $valor)->get();

// Funciona também em colunas JSON
Espelho::query()->searchAny(['dados->nome', 'dados->endereco'], $valor)->get();
```

```php
// Considere criar indices nas colunas JSON para melhorar a performance
DB::statement("CREATE INDEX idx_meu_indice ON minha_tabela USING gin (immutable_unaccent(minha_coluna->>'meu_campo') gin_trgm_ops)");
```

<br>

# Paginação

Utilize o trait `WithPaginationAndReset` nas telas com tabelas.  
Quando os filtros forem alterados, a paginação será resetada automaticamente.

```php
use TJDFT\Laravel\Traits\WithPaginationAndReset;
// ...

new class extends Component {

    use WithPaginationAndReset;

    // ...
}
```

Limpa propriedades de filtro e resetar paginação.

```html
<!-- Invoca manualmente o reset de paginação e propriedades de filtro -->

<x-button label="Limpar" wire:click="clear()" />
```

<br>

# Ícones

Este pacote inclui um conjunto extra de ícones para utilização nos componentes do **maryUI**.

- https://lucide.dev/icons **(preferencial)**
- https://heroicons.com
- https://icons.getbootstrap.com

```html
<!-- Hero Icons possuem prefixo "o-" -->
<x-button label="Salvar" icon="o-check" />

<!-- Lucide Icons possuem prefixo "lucide." -->
<x-button label="Consulta" icon="lucide.users" />

<!-- Bootstrap Icons possuem prefixo "bi." -->
<x-button label="Contato" icon="bi.whatsapp" />
```

<br>

# Lazy spinner

Para componentes  `lazy` , adicione o trait `HasSpinnerPlaceholder` para exibir um spinner de carregamento enquanto o componente é renderizado.

```html
<!-- Adicione `lazy` -->
<livewire:algum-componente lazy />
```

```php
# Componente lazy

use TJDFT\Laravel\Traits\HasSpinnerPlaceholder;
// ...

new class extends Component {

    use HasSpinnerPlaceholder;
    
    // ...
}
```

<br>

# Validação (pt-BR)

As mensagens de validação estão traduzidas.

```php
'required' => 'O campo :attribute é obrigatório.'

// ...
```

Validações adicionais para formatos dos sistemas brasileiros via  `laravellegends/pt-br-validator`.

```php
// Verifica se é um número de CPF válido
#[Validate('required|cpf')]
public ?string $cpf = null;

// Valida se é num formato válido de CEP
#[Validate('nullable|formato_cep')]
public ?string $cep = null;
```

<br>

# Exceptions

Utilize a classe `AppException` na lógica de negócio para automaticamente exibir um **toast** do **maryUI**.

```php
use TJDFT\Laravel\Exceptions\AppException;
// ...

if ($consignacao->status_id === Status::FINALIZADA) {
    throw new AppException("Este contrato não pode ser alterado.");
}
```

# Casts

Casts personalizados para formatação de atributos em models Eloquent.

```php
use TJDFT\Laravel\Casts\NumeroCast;
use TJDFT\Laravel\Casts\CompetenciaCast;
// ...

class Estagiario extends Model
{
    // ...

    protected function casts(): array 
    {
        return [
            'cpf' => NumeroCast::class,            # Unidirecional: Remove formatação e grava apenas números.
            'semestre' => CompetenciaCast::class   # Bidirecional: Grava no formato `anomes` e recupera um `Carbon`. 
        ];
    }
}
``` 

<br>

<br>

# Número

```php
use TJDFT\Laravel\Support\Numero; 

Numero::percentual('0.2567')        # 25,67%
Numero::percentual('0.2567', 1)     # 25,6%

Numero::truncado('14.6789')         # 14.67
Numero::truncado('14.6789', 3)      # 14.678

Numero::formatado('1234.56')        # 1.234,56

Numero::moeda('1234.56')            # R$ 3.201,45

Numero::cpf('12345678901')          # 123.456.789-01
Numero::cnpj('12345678000195')      # 12.345.678-0001/95
```

<br>

# Data

```php
use TJDFT\Laravel\Support\Data;

Data::formatada("2025-04-12")     # 12/04/2025
Data::formatada(null, "-")        # Se for nula mostra "-"
Data::formatada($carbon, "-")     # Funciona também com objetos Carbon.
```

<br>

# Telefone

```php
use TJDFT\Laravel\Support\Telefone;

Telefone::formatado('11987654321')   # (11) 98765-4321
Telefone::formatado('987654321')     # 98765-4321
Telefone::formatado(null, "-")       # Se for nulo mostra "-"
```

<br>

# Auditoria

Utilize o trait `Auditavel` e implemente a interface `AuditavelContract` nos models que deseja auditar.

```php
use Illuminate\Database\Eloquent\Model;
use TJDFT\Laravel\Contracts\AuditavelContract;
use TJDFT\Laravel\Traits\Auditavel;

// ...

class Pagina extends Model implements AuditavelContract
{
    use Auditavel;
    
    // ...
}

```

Para visualizar os registros de auditoria utilize o componente passando como parâmetro a instância de um model auditável.

```html
<!-- Ex: Página de edição da rubrica" -->

<div>
    <!-- Cabeçalho -->
    <x-header title="Editar Rubrica" separator>
        <x-slot:actions>
            <!-- Histórico de auditoria -->
            <livewire:tjdft::auditoria :model="$rubrica" />
        </x-slot:actions>
    </x-header>

    <!-- Formulário -->
    <x-form>
        ...
    </x-form>
</div>
```

Para renomear os campos exibidos em tela, declare o método de configuração `auditoria()` no model.

```php
class Pagina extends Model implements AuditavelContract
{
    use Auditavel;
    
    // ...
    
    protected function auditoria(): array
    {
        return [
            'titulo' => 'Título',
            'conteudo' => 'Conteúdo',        
        ];
    }
}
```

Para exibir o valor final de relacionamentos declare o campo `transform` indicando o model relacionado.

```php
use App\Models\TipoPagina;

// ...

// A coluna `tipo_id` é a FK
public function tipo(): BelongsTo
{    
    return $this->belongsTo(TipoPagina::class);
}

protected function auditoria(): array
{
    return [
        'titulo' => 'Título',
        'conteudo' => 'Conteúdo',
        'tipo_id' => [
            'nome' => 'Tipo da Pagina',
            'transform' => TipoPagina::class,  // Exibe o valor do campo `nome` do model TipoPagina
        ],  
    ];
}
```

Quando colunas `JSON` são auditadas, utilize o plugin `Diff2HTML` para exibir automaticamente as diferenças de maneira amigável.

```html
<!-- /resources/views/layouts/app.blade -->

<html>
<head>
    ...

    <!-- Diff2HTML -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/xcode.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/diff2html@3.4.48/bundles/css/diff2html.min.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/diff2html@3.4.48/bundles/js/diff2html-ui.min.js"></script>
</head>
```

Caso não queira exibir o `JSON` completo, é possível configurar para exibir apenas campos específicos.

```php
protected function auditoria(): array
{
    return [
        'titulo' => 'Título',
        'conteudo' => 'Conteúdo',  
        'unidade_responsavel' => [ 
            'nome' => 'Unidade Responsável',
            'campos' => ['sigla', 'codigo'],    // Não é necessário usar `Diff2HTML`
        ],
    ];
}
```

<br>

# Testes

**EXEMPLO: `login()`**

```php
// Cria e autentica um usuário aleatoriamente
$this->login();

// Cria e autentica um usuário com permissão específica
$this->login(permission: 'comprovante.visualizar');

// Cria e autentica um usuário com múltiplas permissões
$this->login(permission: ['comprovante.visualizar', 'comprovante.gerenciar']);

// Autentica um usuário existente
$user = User::factory()->create();
$this->login($user);
```

**EXEMPLO: `login()`**

```php
test('Usuários autenticados podem ver páginas secretas', function () {        

     // Dado que eu estou logado
     // Então eu consigo ver a página
     $this->get('/pagina-secreta')->assertOk();

    // Não é necessário usar `$this->login()`
    // Pois o `TestCase` já faz isso automaticamente antes de cada teste.
});
```

**EXEMPLO: `login()`**

```php
test('Teste permissão', function () {

    // Dado que eu tenho permissão básica
    $this->login(permission: 'paginas.visualizar');
    
    // Quando eu tentar editar a página, então verei um erro de acesso negado
    $this->get('/paginas/99/edit')->assertForbiden();
    
    // Dado que eu tenho permissão de gestão
    $this->login(permission: 'paginas.gerenciar');
    
    // Quando eu tentar editar a página, então eu consigo ver a página
    $this->get('/paginas/99/edit')->assertOk();
});
```

**EXEMPLO: `logout()`**

```php
test('Visitantes não podem ver páginas secretas.', function () {         

    // Dado que eu não estava logado
    $this->logout();

    // Quando eu tentar acessar uma rota protegida
    // Então sou redirecionado para a página de login
    $this->get('/pagina-secreta')->assertRedirect('/login');
});
```

**EXEMPLO: `assertPolvoQueryContains()`**

```php
test('Consulta movimentações', function () {

    // Quando eu definir o período e consultar
    Livewire::test('pages::movimentacoes')
        ->set('data_inicio', '2020-07-20')
        ->set('data_fim', '2020-07-22')
        ->call('consultar');

    // Então a query GraphQL que foi executada pelo PolvoService deve conter o período correto
    $this->assertPolvoQueryContains('
            movimentacoes(
                periodo: {dataInicio: "2020-07-20" dataFim: "2020-07-22"}                
    ');
});
````

**EXEMPLO: `assertPolvoQueryNotContains()`**

```php
// Dado que eu estou visualizando a unidade `12345`
$this->get('/unidades/12345');

// Então query GraphQL que foi executada pelo PolvoService NÃO contém um trecho esperado
$this->assertPolvoQueryNotContains("localizacao (codigo: 'errado') ";
````

<br>

# GraphQL Faker

Este pacote expõe o endpoint `/graphql-faker` para simular respostas de APIs graphQL externas.

1. Ajuste `phpunit.xml`.

<!-- @formatter:off -->
```xml
<env name="TJDFT_POLVO_API_URL" value="http://localhost:8080/graphql-faker"/>
```
<!-- @formatter:on -->

2. Obtenha o schema **SDL** original da API RH e salve como `tests/faker.graphql`.

```shell
# Pode ser obtido executando este comando no terminal da API RH.

php artisan lighthouse:print-schema > schema.graphql
```

3. Crie o arquivo `tests/faker.graphql.php`

```php
<?php

use Faker\Factory;

$faker = Factory::create();

/*
 * Sobrescreve valores aleatórios do faker graphQL para casos específicos.
 *
 */

/**
 * EXEMPLO:
 * 
 * Em algumas situações, é necessário que determinados campos tenham valores conhecidos ou "datas de início e fim" coerentes, para validação de regras.
 * Pois, caso contrário, os testes podem falhar de maneira intermitente.
 */
return [
    'CapacitacaoParticipante.aprovado' => true,
    'Afastamento.dataInicio' => '2021-01-01',
    'Afastamento.dataFim' => '2021-01-31',
];
```

<br>

# Desenvolvimento local

Execute o clone na raiz da sua aplicação.

```shell
git clone git@github.com:tjdft/laravel.git packages/laravel
```

Adicione o repositório local no `composer.json` da aplicação.


<!-- @formatter:off -->
```shell
composer config repositories.local '{"type": "path", "url": "/var/www/html/packages/laravel"}'  
```
<!-- @formatter:on -->

Instale a versão local do pacote.

```shell
composer require tjdft/laravel:@dev
```

**Pronto!**

Para voltar a utilizar a versão do Packagist.

```shell
composer config --unset repositories.local
composer require tjdft/laravel
```

---

Testes automatizados do pacote.

```shell
# Entre na pasta do pacote
cd /var/www/html/packages/laravel

# Instale as dependências
composer install

# Rode os testes
composer test

# Cobertura de código
composer test:coverage
```
