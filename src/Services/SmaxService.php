<?php

namespace TJDFT\Laravel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TJDFT\Laravel\Exceptions\SmaxException;
use TJDFT\Laravel\Mail\FeedbackMail;

/**
 * Serviço de Integração com o SMAX
 */
class SmaxService
{
    // Em segundos
    public const TOKEN_EXPIRATION_TIME = 3500;

    public function criarRequisicao(string $conteudo, ?string $url = null): void
    {
        $user = auth()->user();

        try {
            $personID = $this->getPersonID($user->login);

            // HTML
            $description = $conteudo .
            "
                        <br /><br />
                        -----------------------------------          <br />
                         Via integração " . config('app.name') . "   <br />
                         -----------------------------------         <br />
                        <strong>Nome:</strong> {$user->nome}         <br />
                        <strong>Email:</strong> {$user->email}       <br />
                        <strong>URL:</strong> " . $url ?? '-' . "    <br />
                        ";

            $resposta = Http::retry(3, 2000)
                ->withHeaders($this->getHeader())
                ->post(config('tjdft.smax.api_url') . '/bulk', [
                    "entities" => [
                        [
                            "entity_type" => "Request",
                            "properties" => [
                                "RequestsOffering" => config('tjdft.smax.requests_offering'),
                                "RequestedByPerson" => $personID,
                                "DisplayLabel" => "Feedback - " . config('app.name'),
                                "Description" => $description
                            ]

                        ]
                    ],
                    "operation" => "CREATE"
                ])
                ->json();

            if ($resposta['meta']['completion_status'] !== 'OK') {
                $motivo = $resposta['entity_result_list'][0]['errorDetails']['message'] ?? 'erro desconhecido';

                throw new SmaxException("Falha ao criar requisição no SMAX. Motivo: " . $motivo);
            }
        } catch (Throwable $e) {
            // Reporta silenciosamente o erro (SENTRY)
            report($e);

            // Quando a integração falha um e-mail é enviado para a equipe. Veja `TJDFT_SMAX_FALLBACK_EMAILS` no arquivo de configuração.
            Mail::sendNow(new FeedbackMail($user, $conteudo, $url));
        }
    }

    /**
     * Obtém cabeçalho padrão para as requisições.
     */
    private function getHeader(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Cookie' => "LWSSO_COOKIE_KEY=" . $this->getToken()
        ];
    }

    /**
     * Obtém o token e coloca em cache pelo prazo estabelecido.
     */
    private function getToken(): string
    {
        return cache()->remember('smax_token', self::TOKEN_EXPIRATION_TIME, function () {
            try {
                return Http::retry(3, 2000)
                    ->post(config('tjdft.smax.auth_url'), [
                        'Content-Type' => 'application/json',
                        "Login" => config('tjdft.smax.auth_login'),
                        "Password" => config('tjdft.smax.auth_password'),
                    ])
                    ->getBody()
                    ->getContents();
            } catch (Throwable $e) {
                throw new SmaxException("Falha ao obter token do SMAX. Motivo: " . $e->getMessage());
            }
        });
    }

    /**
     * Obtém o PersonID a partir do login do usuário.
     */
    private function getPersonID(string $login): mixed
    {
        $employeeURL = config('tjdft.smax.api_url') . "/Person?layout='EmployeeNumber'&filter=Upn='$login'";

        $resposta = Http::retry(3, 2000)
            ->withHeaders($this->getHeader())
            ->get($employeeURL)
            ->json();

        return $resposta['entities'][0]['properties']['Id'] ?? throw new SmaxException("Usuário sem ID no SMAX");
    }
}
