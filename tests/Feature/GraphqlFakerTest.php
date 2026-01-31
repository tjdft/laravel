<?php

beforeEach(function () {
    // Define o ambiente como `local`
    app()->detectEnvironment(fn() => 'local');
});

test('Expõe o endpoint GraphQL Faker apenas em ambiente local', function () {
    // Simula o ambiente como `production`
    app()->detectEnvironment(fn() => 'production');

    $this->post('/graphql-faker')
        ->assertForbidden()
        ->assertSeeText('GraphQL faker permitido apenas com `APP_ENV=local`.');
});

test('Retorna dados fake conforme o schema e overrides definidos', function () {
    $this->post('/graphql-faker',
        [
            'query' => '
                {
                    capacitacoes {
                        aprovado
                        dataInicio
                        dataFim
                    }
                }
        '
        ])
        ->assertOk()
        ->assertJson([
            'data' => [
                'capacitacoes' => [
                    [
                        'aprovado' => true,
                        'dataInicio' => '2021-01-01',
                        'dataFim' => '2021-01-31',
                    ],
                ]
            ],
        ]);
});

test('Retorna erro formatado para campo inválido.', function () {
    $this->post('/graphql-faker',
        [
            'query' => '
                {
                    capacitacoes {
                        nomeMae
                    }
                }
        '
        ])
        ->assertOk()
        ->assertJson([
            'errors' => [
                [
                    'message' => 'Cannot query field "nomeMae" on type "CapacitacaoParticipante".',
                ],
            ],
        ]);
});

test('Retorna múltiplos registros para listas.', function () {
    $this->post('/graphql-faker',
        [
            'query' => '
                {
                    capacitacoes {
                        id
                        codigo
                        aprovado
                        isVinculado
                        dataSistema
                    }
                }
        '
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'capacitacoes' => [
                    '*' => [
                        'id',
                        'codigo',
                        'aprovado',
                        'isVinculado',
                        'dataSistema',
                    ],
                ]
            ],
        ]);
});
