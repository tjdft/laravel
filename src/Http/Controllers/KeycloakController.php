<?php

namespace TJDFT\Laravel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use LogicException;
use Throwable;
use TJDFT\Laravel\Services\PessoasPolvoService;

class KeycloakController
{
    public function login(Request $request)
    {
        // Se estiver logado redireciona para o dashboard
        if (auth()->user()) {
            return redirect('/');
        }

        $redirect = '/auth/redirect/keycloak';

        // Quando for via wire:navigate
        if ($request->header('Sec-Fetch-Mode') == 'cors') {
            return response("Autenticando ...   <script>window.location.href = '{$redirect}'; </script>");
        }

        return redirect($redirect);
    }

    public function redirect()
    {
        return Socialite::driver('keycloak')
            ->setScopes(['openid', 'email'])
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        // Se estiver logado redireciona para o dashboard
        if (auth()->user()) {
            return redirect('/');
        }

        try {
            // Dados retornados do Keycloak
            $keycloakUser = Socialite::driver('keycloak')->stateless()->user();

            // CPF do usuário no Keycloak
            $cpf = $keycloakUser->attributes['cpf'] ?? $keycloakUser->user['cpf'] ?? $keycloakUser->user['cpf'][0] ?? null;

            if (! $cpf) {
                throw new LogicException('Usuário não possui CPF cadastrado no Keycloak.');
            }

            // Obtém dados do POLVO API RH
            $pessoas = new PessoasPolvoService()->porCpf($cpf);

            // Usuários externos que não possuem cadastro no RH, portanto cria-se o usuário aqui (Ex: terceirizados)
            if ($pessoas->count() == 0) {
                $pessoas->push([
                    'cpf' => $cpf,
                    'matricula' => $cpf,
                ]);
            }

            /** @var Model $model */
            $model = config('auth.providers.users.model');

            // Registra localmente
            $pessoas->each(function ($pessoa) use ($keycloakUser, $cpf, $model) {
                $model::updateOrCreate(
                    [
                        'cpf' => $cpf,
                        'matricula' => $pessoa['matricula'],
                    ],
                    [
                        'uuid' => $keycloakUser->getId(),
                        'login' => $keycloakUser->getNickname(),
                        'foto' => $pessoa['fotoUri'] ?? $pessoa['foto'] ?? null,
                        'nome' => $pessoa['nomeFinal'] ?? $keycloakUser->getName(),
                        'email' => $keycloakUser->getEmail(),
                        'localizacao' => $pessoa['localizacao'] ?? null,
                        'rh_tipo' => $pessoa['tipo'] ?? null,
                        'rh_status' => $pessoa['status'] ?? null,
                    ]
                );
            });

            // Verifica localmente o cadastro
            $users = $model::where('cpf', $cpf)->get();

            if (! $users->count()) {
                throw new AuthorizationException("O CPF ({$cpf}) não está cadastrado ou não possui acesso nesta aplicação.");
            }

            // Autentica usuário na aplicação
            auth()->login($users->first());

            // Invoca action para ajuste de permissões
            try {
                app()->make(config('tjdft.permissions_action'), ['user' => $users->first()])->execute();
            } catch (Throwable $e) {
                // Suprime erro caso a classe não exista
            }

            // Se houver mais de um cadastro, redireciona para página de escolha
            if ($users->count() > 1) {
                return redirect('/auth/perfil');
            }
        } catch (Throwable $th) {
            throw new AuthorizationException('Erro ao fazer login: '.$th->getMessage());
        }

        // Redireciona de volta para página que estava tentando acessar.
        // Se não especificado, por padrão vai para `/`
        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        // Gera URL de logout do Keycloak
        $url_logout = Socialite::driver('keycloak')->getLogoutUrl(config('app.url'), config('tjdft.keycloak.client_id'));

        // Desloga na aplicação
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redireciona para rota de logout do keycloak
        return redirect($url_logout);
    }
}
