<?php

namespace TJDFT\Laravel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use LogicException;
use Throwable;
use TJDFT\Laravel\Services\PessoasPolvoService;

class KeycloakController extends Controller
{
    private PessoasPolvoService $pessoasPolvoService;

    public function __construct()
    {
        $this->pessoasPolvoService = new PessoasPolvoService;
    }

    public function login(Request $request)
    {
        // Se estiver logado redireciona para o dashboard
        if (Auth::user()) {
            return redirect('/');
        }

        $redirect = '/auth/redirect/keycloak';

        // Quando for via wire:navigate
        if ($request->header('Sec-Fetch-Mode') == 'cors') {
            echo "Autenticando ...   <script>window.location.href = '{$redirect}'; </script>";
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
        if (Auth::user()) {
            return redirect('/');
        }

        try {
            // Dados retornados do Keycloak
            $keycloakUser = Socialite::driver('keycloak')->stateless()->user();

            // Cpf do usuário no Keycloak
            $cpf = $keycloakUser['cpf'][0] ?? null;

            if (! $cpf) {
                throw new LogicException('Usuário sem CPF cadastrado no Keycloak.');
            }

            // Obtém dados do RH
            $pessoas = $this->pessoasPolvoService->porCpf($cpf);

            // Registra localmente
            $pessoas->each(function ($pessoa) use ($keycloakUser, $cpf) {
                User::updateOrCreate(
                    [
                        'cpf' => $cpf,
                        'matricula' => $pessoa['matricula']
                    ],
                    [
                        'uuid' => $keycloakUser->getId(),
                        'login' => $keycloakUser->getNickname(),
                        'foto' => $pessoa['foto'] ?? null,
                        'nome' => $pessoa['nomeFinal'] ?? $keycloakUser->getName(),
                        'email' => $keycloakUser->getEmail(),
                        'rh_tipo' => $pessoa['tipo'],
                        'rh_status' => $pessoa['status'],
                    ]
                );
            });

            // Verifica localmente o cadastro
            $users = User::where('cpf', $cpf)->get();

            if (! $users->count()) {
                throw new LogicException('Usuário não registrado com CPF: ' . $cpf);
            }

            // Autentica usuário na aplicação
            Auth::login($users->first());

            // Invoca action para ajuste de permissões
            try {
                $action = app()->make(config('tjdft.permissions_action'));
                new $action($users->first())->execute();
            } catch (Throwable $e) {
                // Sumprime erro caso a classe não exista
            }

            // Se houver mais de um cadastro, redireciona para página de escolha
            if ($users->count() > 1) {
                return redirect('/auth/perfil');
            }
        } catch (Throwable $th) {
            throw new LogicException('Erro ao fazer login. ' . $th->getMessage());
        }

        // Redireciona de volta para página que estava tentando acessar. Se não especificado, por padrão vai para `/`
        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        // Gera URL de logout do Keycloak
        // Os ambientes estão com versões diferentes do keycloak.
        // O processo de logout é diferente.
        $url_logout = app()->environment('production')
            ? Socialite::driver('keycloak')->getLogoutUrl(config('app.url'))
            : Socialite::driver('keycloak')->getLogoutUrl(config('app.url'), config('tjdft.keycloak.client_id'));

        // Desloga na aplicação
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redireciona para rota de logout do keycloak
        return redirect($url_logout);
    }
}
