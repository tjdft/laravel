<p align="center"><img width="200" src="logo.png"></p>

<p align="center">
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://img.shields.io/packagist/dt/tjdft/laravel?cacheSeconds=60">
    </a>
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://img.shields.io/packagist/v/tjdft/laravel?label=stable&color=blue&cacheSeconds=60">
    </a>
    <a href="https://packagist.org/packages/tjdft/laravel">
        <img src="https://poser.pugx.org/tjdft/laravel/license.svg">
    </a>
</p>

# Introdução

Pacote unificado para desenvolvimento de aplicações Laravel no TJDFT.

**Autenticação e Autorização:**

- Fluxo de autenticação com **Keycloak**.
- Funcionalidade de **Impersonate**.
- Gerenciamento de **Permissões**.

**Integração com o RH:**

- Classe base para consulta na **API RH**.
- Desambiguação de perfil para pessoas com **múltiplos vínculos no RH**.

**Funcionalidades adicionais:**

- Integração com o **Sentry**
- Trait `HasSearchAny` para busca simplificada em **múltiplos campos**.
- Trait `WithPaginationAndReset` para paginação simplificada com **Livewire**.
- Utilitários `Numero` e `Data` para diversas **formatações** em tela.
- Pacote extra de **ícones**.
- Tela de **erro** padronizada.
- Classes de **exception** padronizadas.
- Arquivos de **translation** em `pt_BR`.
- Ativa extensões úteis do **PostgreSQL**.

<br>

## Pré-requisitos

```
composer require robsontenorio/mary
php artisan mary:install
```

<br>

## Instalação

**Adicione o pacote.**

```bash
composer require tjdft/laravel
```

**Altere o timezone em `config/app.php`**

```php
'timezone' => 'America/Sao_Paulo',
```

**Altere o idioma em `.env`**

```bash
APP_LOCALE=pt_BR
```

**Adicione as configurações de middleware e exceptions em `bootstrap/app.php`.**

```php
use TJDFT\Laravel\ExceptionHandler;

...

->withMiddleware(function (Middleware $middleware) {
    // Openshift proxy
    $middleware->trustProxies(at: '*');
})
->withExceptions(function (Exceptions $exceptions) {
    // Tratamento personalizado de exceções
    ExceptionHandler::register($exceptions);
})
```

**Crie as novas variáveis de ambiente em `.env`.**

```bash
# Sentry
TJDFT_SENTRY_LARAVEL_DSN=

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

# Schema onde devem ser ativadas as extensões do PostgreSQL
# Use apenas se o schema principal da aplicação for diferente de `public`.
TJDFT_PGSQL_EXTENSIONS_SCHEMA=core
```

**Ajuste a migration existente `users`.**

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique()->index()->nullable();
    $table->string('login')->unique()->index();
    $table->string('matricula')->unique()->nullable();
    $table->string('cpf')->index()->nullable();
    $table->string('nome');
    $table->string('email')->nullable();
    $table->string('foto')->nullable();
    $table->json('localizacao')->nullable();
    $table->string('rh_tipo')->nullable();
    $table->string('rh_status')->nullable();
    $table->timestamps();

    $table->unique(['cpf', 'matricula']);
    $table->index(['cpf', 'matricula']);
});
```

**Rode as migrations.**

```bash
# Esta ação destruirá e recriará o banco!

php artisan migrate:fresh --seed
```

**Pronto!**

<br>

## Autenticação

**Este pacote implementa o fluxo de autenticação via **Keycloak** para as rotas protegidas do sistema.**

```php
// Rotas protegidas 
Route::middleware('auth')->group(function () {   
    
    Route::livewire('/paginas/create', 'pages::paginas.criar');
    
    // ...
});
```

**Para o **logout** de usuários utilize a rota `/auth/logout/keycloak`.**


<!-- @formatter:off -->
```html
<x-button icon="lucide.power" title="Sair" link="/auth/logout/keycloak" no-wire-navigate />
```
<!-- @formatter:on -->

**Usuários com mais de um vínculo no RH serão redirecionados automaticamente para a rota `/auth/perfil`.**

```
Ex: Se o usuário possui vínculo de Pensão Alimentícia e Servidor, então ele deve selecionar um perfil para acesso.
```

**Consulte também o tópico [Autorização](#autorização) para mais detalhes sobre **permissões**.**

```php
pubfic function mount(): void 
{    
    // Lança uma exceção 403 se o usuário não tiver a permissão
    auth()->user()->authorize("comprovante.visualizar");   
}
```

```html
<!-- Se não tem a permissão, oculta o menu -->
<x-menu-item title="Criar Página" link="/paginas/create" :hidden="auth()->user()->cannot('paginas.criar')" />
```

<br>

## Impersonate

Utilize a rota `/auth/impersonate` para a funcionalidade de personificação de usuários.  
Somente usuários com a permissão `impersonate` podem acessar esta funcionalidade.  

**Adicione no arquivo de layout o aviso de personificação, quando em uso.**

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

## API RH

**Este pacote possui a classe base para consultas na API RH.**

```php
class PolvoService { ... }
```

**Crie serviços de consulta baseados na classe `TJDFT\Laravel\Services\PolvoService`.**

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

**Todas as consultas GraphQL tem um prazo de cache padrão de **1 hora**.**

```bash
TJDFT_POLVO_CACHE_TTL='1 hour'
```

**Pra definir um prazo específico apenas para algumas consultas, utilize o método `lembrar()`.**

```php
// aceita qualquer string válida do `Carbon`

$ferias = new FeriasPolvoService()->lembrar('1 day')->porMatricula("12345");
```

**Para desabilitar o cache em consultas específicas, utilize o método `semCache()`.**

```php
$ferias = new FeriasPolvoService()->semCache()->porMatricula("12345");
```

**Para desabilitar completamente o cache tem todas as consultas GraphQL ajuste a variável de ambiente.**

```bash
TJDFT_POLVO_CACHE_TTL='0'
```

<br>

## Pesquisa

**Adicione o trait `HasSearchAny` nos models.**

```php
use TJDFT\Laravel\Traits\HasSearchAny;

class Rubrica extends Authenticatable
{
    use HasFactory, Notifiable, HasGrant, HasSearchAny;
    
    //...
}
```

**Exemplos de consultas.**

```php
// Pesquisa em múltiplos campos, tratando acentuação e case sensitive automaticamente.
Rubrica::query()->searchAny(['nome', 'sigla'], $valor)->get();

// Funciona também em colunas JSON
Espelho::query()->searchAny(['dados->nome', 'dados->endereco'], $valor)->get();
```

**Exemplo de índice.**

```php
// Considere criar indices nas colunas JSON para melhorar a performance
DB::statement("CREATE INDEX idx_meu_indice ON minha_tabela USING gin (immutable_unaccent(minha_coluna->>'meu_campo') gin_trgm_ops)");
```

<br>

## Número

```php
use TJDFT\Laravel\Support\Numero; 

Numero::porcentagem('0.2567')       # 25,67 %

Numero::formatado('1234.56')        # 1.234,56
Numero::moeda('1234.56')            # R$ 3.201,45
Numero::truncado('14.6789')         # 14.67
Numero::truncado('14.6789', 3)      # 14.678

Numero::cpf('12345678901')          # 123.456.789-01
Numero::cnpj('12345678000195')      # 12.345.678-0001/95
```

<br>

## Data

```php
use TJDFT\Laravel\Support\Data;

Data::formatada("2025-04-12")     # 12/04/2025
Data::formatada(null, "-")        # Se for nula mostra "-"
Data::formatada($carbon, "-")     # Funciona também com objetos Carbon.
```

<br>

## Paginação

Utilize o trait `WithPaginationAndReset` nas telas com tabelas para reset automático de paginação, quando as propriedades de filtro forem atualizadas.

```php
use TJDFT\Laravel\Traits\WithPaginationAndReset;

new class extends Component {
    use WithPaginationAndReset;

    // ...
}
```

<br>

## Exceptions

Utilize a classe `AppException` na lógica de negócio para automaticamente exibir um **toast** do **maryUI**.

```php
use TJDFT\Laravel\Exceptions\AppException;

...

if ($consignacao->status_id === Status::FINALIZADA) {
    throw new AppException("Este contrato não pode ser alterado.");
}
```

<br>

## Ícones

Este pacote inclui um conjunto extra de ícones para utilização nos componentes do **maryUI**.

- https://lucide.dev/icons **(recomendado)**
- https://heroicons.com
- https://materialdesignicons.com

```html
<!-- Hero icons possuem prefixo "o-" -->
<x-button label="Salvar" icon="o-check" />

<!-- Lucide icons possuem prefixo "lucide." -->
<x-button label="Consulta" icon="lucide.users" />

<!-- MDI icons possuem prefixo "mdi." -->
<x-button label="Contato" icon="mdi.whatsapp" />
```

<br>

## Autorização

Utilize a rota `/auth/permissions` para acessar o gerenciamento de permissões.  
Somente usuários com a permissão `permissions.gerenciar` podem acessar esta funcionalidade.


**Adicione o trait `HasGrant` no model `User`.**

```php
use TJDFT\Laravel\Traits\HasGrant;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasGrant;
    
    //...
}
```

**Estas roles e permissions são registradas automaticamente pelo pacote.**

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

**EXEMPLO: crie outras roles e permissions na sua aplicação.**

```php
// database/seeders/PermissionsSeeder.php

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissão inicial criada pelo pacote
        if (Permission::where('name', '<>', 'permissoes.gerenciar')->count()) {
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
        // Confira o exmplo de roles/permissions dinâmicas abaixo.
    }
}
```

**EXEMPLO: lógica personalizada para definir dinamicamente roles/permissions.**

```php
// Esta clase é chamada automaticamente após o login do usuário.
// app/Actions/AtualizarPermissionsLoginAction.php

<?php

namespace App\Actions;

use App\Models\User;

class AtualizarPermissionsLoginAction
{
    public function __construct(private User $user)
    {
    }

    public function execute(): void
    {
        // Baseado nos dados do usuário, defina uma lógica para atribuição de roles.
        // Exemplo: se é um `SERVIDOR`, atribua a role 'funcionario'.
        
        if ($this->user->rh_tipo === 'SERVIDOR') {
            $this->user->assignRole('funcionario');
        }
    }
}
```

**Adicione aos seeders padrão.**

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

**Rode as migrations.**

```bash
# Esta ação destruirá e recriará o banco!

php artisan migrate:fresh --seed
```

<br>

## Desenvolvimento local

Crie a pasta `packages` na raiz da sua aplicação e clone o repositório.

```shell
mkdir packages
cd packages
git clone git@github.com:tjdft/laravel.git
```

Modifique o  `composer.json` da sua aplicação.


<!-- @formatter:off -->
```json
"minimum-stability": "dev", // <- mude pa "dev"  

// Adicione este trecho
"repositories": {
  "tjdft/laravel": {
    "type": "path",
    "url": "/var/www/html/packages/laravel",
    "options": {
        "symlink": true
      }
  }
}
```
<!-- @formatter:on -->

Instale o pacote novamente para realizar o `symlink` local.

```shell
composer require tjdft/laravel
```
