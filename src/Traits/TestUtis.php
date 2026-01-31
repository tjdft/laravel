<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Services\PolvoService;

trait TestUtis
{
    use LazilyRefreshDatabase;

    /**
     * Loga um usuário específico ou cria um aleatório.
     * É possível definir uma role e/ou permissões para o usuário a ser criado.
     */
    public function login(?Authenticatable $user = null, ?string $role = null, array|string|null $permission = null): Authenticatable
    {
        /** @var Model $model */
        $model = config('auth.providers.users.model');

        $user = $user ?? $model::factory()->create();

        if ($role) {
            $user->assignRole($role);
        }

        if ($permission) {
            $user->givePermissionTo($permission);
        }

        $this->actingAs($user);

        return $user;
    }

    /**
     * Faz logout do usuário atual.
     */
    public function logout(): void
    {
        auth()->logout();
    }

    /**
     * Verifica se query graphQL executada NÃO contém um trecho.
     */
    public function assertPolvoQueryNotContains(string $valor): void
    {
        $valor = $this->formataQuery($valor);
        $query = $this->formataQuery($this->getQuery());

        $this->assertStringNotContainsString($valor, $query);
    }

    /**
     * Verifica se query graphQL executada contém um trecho.
     */
    public function assertPolvoQueryContains(string $valor): void
    {
        $valor = $this->formataQuery($valor);
        $query = $this->formataQuery($this->getQuery());

        $this->assertStringContainsString($valor, $query);
    }

    /**
     * Método de inicialização da suíte de testes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Roda os seeders logo após as migrations
        if (class_exists('Database\Seeders\DatabaseSeeder')) {
            $this->seed = true;
        }

        // Chama método boot() da classe hospedeira se existir
        if (method_exists($this, 'boot')) {
            $this->boot();
        }

        // Polvo no modo Fake
        PolvoService::fake();

        // Por padrão um usuário aleatório já está logado
        $this->login();

        // Desliga o Vite
        $this->withoutVite();

        // Bloqueia TODAS as requisições HTTP externas não mockadas.
        Http::preventStrayRequests();

        // Exceto ao Polvo fake que roda localmente. Veja `TJDFT_POLVO_API_URL` em `phpunit.xml`
        Http::allowStrayRequests(["http://localhost:*/graphql-faker"]);
    }

    /**
     * Recupera a query graphQL executada pelo Polvo.
     */
    private function getQuery(): string
    {
        $polvo = resolve(PolvoService::class);

        return $polvo->getQuery();
    }

    /**
     * Formata a query graphQL executada removendo todos os espaços e quebras de linha.
     */
    private function formataQuery(string $query): string
    {
        return str($query)->replace(PHP_EOL, "")->replace(' ', '');
    }
}
