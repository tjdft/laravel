<?php

use TJDFT\Laravel\Traits\CleanStringsBeforeValidation;

describe('CleanStringsBeforeValidation', function () {
    beforeEach(function () {
        $this->instance = new class {
            use CleanStringsBeforeValidation;

            public function handle(array $attributes): array
            {
                return $this->prepareForValidation($attributes);
            }
        };
    });

    it('Faz trim das strings', function () {
        $result = $this->instance->handle([
            'nome' => '  Robson  ',
        ]);

        expect($result)->toBe([
            'nome' => 'Robson',
        ]);
    });

    it('Converte string vazia para null', function () {
        $result = $this->instance->handle([
            'nome' => '   ',
        ]);

        expect($result)->toBe([
            'nome' => null,
        ]);
    });

    it('Mantém valores nao string intactos', function () {
        $result = $this->instance->handle([
            'idade' => 30,
            'ativo' => true,
            'items' => ['a', 'b'],
            'nulo' => null,
        ]);

        expect($result)->toBe([
            'idade' => 30,
            'ativo' => true,
            'items' => ['a', 'b'],
            'nulo' => null,
        ]);
    });

    it('Processa múltiplos atributos', function () {
        $result = $this->instance->handle([
            'nome' => '  Robson ',
            'email' => '   ',
            'idade' => 30,
        ]);

        expect($result)->toBe([
            'nome' => 'Robson',
            'email' => null,
            'idade' => 30,
        ]);
    });
});
