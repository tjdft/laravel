# TJDFT Laravel

Conjunto de utilitários para desenvolvimento de aplicações Laravel no TJDFT.

- Integração com **API RH**.
- Fluxo de autenticação **OAUTH2** via pacote `socialiteproviders/keycloak`.
- Interface para gerenciamento de **permissões**.
- Desambiguação de perfil para pessoas com **múltiplos vínculos**.
- Trait `HasSearchAny` para busca simplificada em **múltiplos campos**.
- Trait `WithPaginationAndReset` para paginação com **Livewire**.
- **Translations** comuns em `pt_BR`.

## Instalação

```bash
composer require tjdft/laravel
```

## Configuração

**Todas as configurações podem ser ajustadas via variáveis de ambiente.**

```bash
# .env

TJDFT_PERMISSION_ACTION=...
TJDFT_KEYCLOAK_REDIRECT_URI=...
TJDFT_POLVO_API_URL
...
```


## Translations

**Altere em `.env`**

```bash
APP_LOCALE=pt_BR
```


## Autenticação

**Crie as variáveis de ambiente em `.env`.**

```bash
# API RH
TJDFT_POLVO_API_URL=https://<URL_API_RH>/graphql
TJDFT_POLVO_AUTH_URL=https://<URL_KEYCLOAK>/auth/realms/<NOME_REALM>/protocol/openid-connect/token
TJDFT_POLVO_CLIENT_ID=
TJDFT_POLVO_CLIENT_SECRET=

# Keycloak
TJDFT_KEYCLOAK_BASE_URL=https://<URL_KEYCLOAK>/auth
TJDFT_KEYCLOAK_REALMS=<NOME_REALM>
TJDFT_KEYCLOAK_CLIENT_ID=
TJDFT_KEYCLOAK_CLIENT_SECRET=
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
    $table->string('rh_tipo')->nullable();
    $table->string('rh_status')->nullable();
    $table->timestamps();

    $table->unique(['cpf', 'matricula']);
    $table->index(['cpf', 'matricula']);
});
```

**Este pacote possui a classe base e o serviço para consultar pessoas na API RH.**

```php
// Classe Base
use TJDFT\Laravel\Services\PolvoService;

// PessoaService
use TJDFT\Laravel\Services\PessoaService;

$pessoa = new PessoaService()->porCpf('12345678901');
$pessoa = new PessoaService()->porLogin('t123456');
```

**Desambiguação de perfil**  
Algumas pessoas possuem mais de um vínculo no RH, ex: Servidor + Pensionista.  
O usuário será redirecionado automaticamente para a tela de seleção de perfil.

**Rotas protegidas**

```php
// routes/web.php

Route::middleware('auth')->group(function () {
   
    Volt::route('/painel', 'painel');
    // ...

});
```


## Autorização

**Adicione o trait `HasGrant` no model `User`.**

```php
use TJDFT\Laravel\Traits\HasGrant;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasGrant;
    
    //...
}
```

**Crie as roles e permissions.**

```php
// database/seeders/PermissionsSeeder.php

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Permission::count()) {
            return;
        }
        
        Permission::create([
            'name' => 'comprovante-rendimentos.processar',
            'description' => 'Comprovantes de Rendimentos - Processar',
        ]);
        
        Permission::create([
            'name' => 'comprovante-rendimentos.visualizar',
            'description' => 'Comprovantes de Rendimentos - Visualizar',
        ]);
        
        // Admin tem todas as permissões
        Role::create([
            'name' => 'admin', 
            'description' => 'Admin'
        ])->givePermissionTo(Permission::all());
        
        // Funcionário tem permissão apenas para visualizar
        Role::create([
            'name' => 'funcionario', 
            'description' => 'Funcionário'
         ])->givePermissionTo([
            'comprovante-rendimentos.visualizar',
        ]);
        
        // Defina os administradores iniciais do sistema
        User::create([
            'cpf' => '0123456789',
            'matricula' => '123456',
            'login' => 't123456',
            'nome' => 'Maria Silva'
        ])->assignRole('admin');
        
        // Note que é inviável atribuir previamente as roles para milhares de `funcionários`.
        // As roles devem ser definidas em tempo de execução após o proceso de login.
        // Veja a seguir.
    }
}
```

**Adicione a verificação de permission nos componentes.**

```php
public function mount(): void 
{
    // Lança uma exceção 403 se o usuário não tiver a permissão
    auth()->user()->authorize("comprovante-rendimentos.visualizar");
}
```

**Defina as roles em tempo de execução.**

```bash
# Esta classe é invocada automaticamente após o login do usuário.

TJDFT_PERMISSION_ACTION=App\Actions\AtualizarPermissionsLoginAction
```

```php
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

**Configure os seeders.**

```php
// database/seeders/DatabaseSeeder

class DatabaseSeeder extends Seeder
{   
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
        ]);
    }
}
```

**Execute**

```bash
php artisan migrate:fresh --seed
```

## Search

**Adicione o trait `HasSearchAny` nos models.**

```php
use TJDFT\Laravel\Traits\HasSearchAny;

class Rubrica extends Authenticatable
{
    use HasFactory, Notifiable, HasGrant, HasSearchAny;
    
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

## Números

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

## Livewire

```php

```

## Exceptions

A classe `AppException` automaticamente mostra um **toast** do **maryUI**.

```php
throw new AppException("Você não pode fazer isso.");
```
