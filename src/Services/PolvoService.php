<?php

namespace TJDFT\Laravel\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Exceptions\PolvoException;
use TJDFT\Laravel\Traits\PolvoCache;
use TJDFT\Laravel\Traits\PolvoPaginador;

/**
 * Serviço de consulta a API RH GraphQL.
 */
class PolvoService
{
    use PolvoCache, PolvoPaginador;

    /** Query executada **/
    protected static string $query = '';

    public function __construct()
    {
        $this->ttl = config('tjdft.polvo.cache_ttl');
    }

    /**
     * Define um token fake.
     * Útil durante testes automatizados, pois evita o login no SSO.
     */
    public static function fake(): void
    {
        // Define um token. Evita ida ao Keycloack durante os testes
        Cache::put('polvo_token', 'fake_token_123');
    }

    /**
     * Executa requisições GraphQL.
     */
    public function graphql(string $query): array
    {
        // Acumula queries executadas durante a requisição para fins de inspeção
        // TODO: isso pode ser um problema nos testes do mesmo arquivo, deveria ser isolado por caso de uso.
        static::$query .= $query;

        // Retorna resposta em cache, se ainda estiver ativa
        if ($this->cache()->isValido()) {
            return $this->cache()->get();
        }

        $response = Http::retry(3, 2000)
            ->withToken($this->getToken())
            ->withOptions(['allow_redirects' => false])
            ->post(config('tjdft.polvo.api_url'), ['query' => $query])
            ->throw(function (Response $response, RequestException $error) {
                throw new PolvoException('PolvoService: ' . $response->status() . ' - ' . $error->getMessage());
            })
            ->json();

        if (! $response) {
            throw new PolvoException('PolvoService: Erro de conectividade, resposta vazia.');
        }

        if (isset($response['errors'])) {
            throw new PolvoException('PolvoService: ' . $response['errors'][0]['message'] ?? 'erro desconhecido.');
        }

        // Coloca resposta da query em cache
        $this->cache()->put($response);

        return $response;
    }

    /**
     * Obtém um token no SSO coloca em cache.
     */
    public function getToken(): string
    {
        // Se o token em cache ainda for válido retorna o mesmo token.
        if (Cache::has('polvo_token')) {
            return Cache::get('polvo_token');
        }

        // Autenticação no SSO
        $response = $this->fetchToken();

        $token = $response['access_token'];
        $validade = $response['expires_in'] - 60;

        // Coloca o token em cache durante o prazo de validade.
        Cache::put('polvo_token', $token, $validade);

        return $token;
    }

    /**
     * Submete requisição com credenciais para obtenção do token.
     */
    public function fetchToken()
    {
        return Http::asForm()
            ->retry(3, 2000)
            ->post(config('tjdft.polvo.auth_url'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('tjdft.polvo.client_id'),
                'client_secret' => config('tjdft.polvo.client_secret'),
            ])->throw(function (Response $response, RequestException $error) {
                throw new PolvoException('Erro obter token Keycloak: ' . $response->status() . ' - ' . $error->getMessage());
            })
            ->json();
    }

    public function getQuery(): string
    {
        return static::$query;
    }
}
