<?php

namespace TJDFT\Laravel\Traits;

use Exception;
use Illuminate\Foundation\Auth\User as Authenticatable;
use TJDFT\Laravel\Services\PessoasPolvoService;

trait HasImpersonate
{
    // Verifica se está personificando outro usuário
    public function impersonating(): bool
    {
        return session()->has('impersonator_user_id');
    }

    public function impersonate(Authenticatable|string $target): void
    {
        auth()->user()->authorize('impersonate');

        // Verifica se o usuário já está personificando alguém
        if (auth()->user()->impersonating()) {
            throw new Exception('Você já está personificando alguém.');
        }

        $user = $target instanceof Authenticatable ? $target : $this->porLogin($target);

        // Prevenir personificação de administradores
        if ($user->isAdmin()) {
            throw new Exception('Não é permitido personificar um administrador.');
        }

        // Armazena o usuário original na sessão
        session()->put('impersonator_user_id', auth()->user()->id);

        session()->regenerate();

        auth()->login($user);
    }

    // Sai da personificação
    public function unpersonate(): void
    {
        $user_id = session()->pull('impersonator_user_id');

        auth()->loginUsingId($user_id);
        session()->regenerate();
    }

    private function porLogin(string $login): Authenticatable
    {
        // Busca a pessoa pelo login informado
        $pessoa = new PessoasPolvoService()->porLogin($login)->first() ?? null;

        // Verifica se a pessoa foi encontrada
        if (! $pessoa) {
            throw new Exception('Login não encontrado.');
        }

        // Verifica se a pessoa possui CPF e matrícula
        if (empty($pessoa['cpf']) || empty($pessoa['matricula'])) {
            throw new Exception('Pessoa sem CPF ou matrícula não pode ser personificada.');
        }

        return self::updateOrCreate(
            [
                'cpf' => $pessoa['cpf'],
                'matricula' => $pessoa['matricula']
            ],
            [
                'login' => $pessoa['login'],
                'foto' => $pessoa['foto'] ?? null,
                'nome' => $pessoa['nomeFinal'] ?? "Sem nome",
                'email' => $pessoa['email'] ?? "Sem e-mail",
                'localizacao' => $pessoa['localizacao'] ?? null,
                'rh_tipo' => $pessoa['tipo'] ?? null,
                'rh_status' => $pessoa['status'] ?? null,
            ]
        );
    }
}
