<?php

namespace TJDFT\Laravel\Services;

use Illuminate\Support\Collection;

class PessoasPolvoService extends PolvoService
{
    public function porCpf(string $cpf): Collection
    {
        $query = '
            {
              pessoas(cpf: "' . $cpf . '" status:[ATIVO, APOSENTADO]) {
                data {
                  matricula
                  nome
                  nomeSocial
                  nomeDeGuerra
                  nomeFinal
                  email
                  login
                  cpf
                  foto
                  tipo
                  status
                  localizacao{
                    id
                    codigo
                    sigla
                    nome
                  }
                }
              }

            }
        ';

        $response = $this->graphql($query);

        return collect($response['data']['pessoas']['data'] ?? []);
    }

    public function porLogin(string $login)
    {
        // Sanitiza
        $login = addslashes($login);
        $login = str_replace("\\'", "'", $login);

        $query = "
            {
              pessoas(login: \"" . $login . "\") {
                data {
                  matricula
                  nome
                  nomeSocial
                  nomeDeGuerra
                  nomeFinal
                  email
                  login
                  foto
                  tipo
                  localizacao{
                    id
                    codigo
                    sigla
                    nome
                  }
                }
              }

            }
        ";

        $response = $this->graphql($query);

        return $response['data']['pessoas']['data'] ?? [];
    }
}
