<?php

namespace TJDFT\Laravel\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Exceptions\PolvoException;

/**
 * Serviço de consulta a API RH GraphQL.
 */
class PolvoService
{
    /** Query executada **/
    protected static string $query;

    /** Configurações do serviço */
    protected array $config;

    public function __construct()
    {
        $this->config = config('tjdft.polvo');
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
        $response = Http::withToken($this->getToken())
            ->retry(3, 2000)
            ->post($this->config['api_url'], ['query' => $query])
            ->throw(function (Response $response, RequestException $error) {
                throw new PolvoException('Erro consultar no PolvoService: ' . $response->status() . ' - ' . $error->getMessage());
            })
            ->json();

        if (isset($response['errors'])) {
            throw new PolvoException('Erro consultar no PolvoService: ' . $response['errors'][0]['message'] ?? 'erro desconhecido');
        }

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
            ->post($this->config['auth_url'], [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
            ])->throw(function (Response $response, RequestException $error) {
                throw new PolvoException('Erro obter token Keycloak: ' . $response->status() . ' - ' . $error->getMessage());
            })
            ->json();
    }
}
