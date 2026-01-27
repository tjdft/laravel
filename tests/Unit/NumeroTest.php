<?php

//use TJDFT\Laravel\Support\Numero;
//
//Numero::porcentagem('0.2567')       # 25,67 %
//
//Numero::formatado('1234.56')        # 1.234,56
//Numero::moeda('1234.56')            # R$ 3.201,45
//Numero::truncado('14.6789')         # 14.67
//Numero::truncado('14.6789', 3)      # 14.678
//
//Numero::cpf('12345678901')          # 123.456.789-01
//Numero::cnpj('12345678000195')      # 12.345.678-0001/95

use TJDFT\Laravel\Support\Numero;

test('Formata número como percentual', function () {
    expect(Numero::percentual('0.2567'))->toBeString('25,67%')
        ->and(Numero::percentual('0.75143', 1))->toBeString('75,1%');
});

test('Formata número com padrão brasileiro', function () {
    expect(Numero::formatado('1234.56'))->toBeString('1.234,56');
});

test('Formata número como moeda', function () {
    expect(Numero::moeda('3201.45'))->toBeString('R$ 3.201,45');
});

test('Trunca número sem arredondar', function () {
    expect(Numero::truncado('14.6789'))->toBeFloat(14.67)
        ->and(Numero::truncado('14.6789', 3))->toBeFloat(14.678);
});

test('Formata CPF corretamente', function () {
    expect(Numero::cpf('12345678901'))->toBeString('123.456.789-01');
});

test('Formata CNPJ corretamente', function () {
    expect(Numero::cnpj('12345678000195'))->toBeString('12.345.678/0001-95');
});

test('Retorna CPF original se inválido', function () {
    expect(Numero::cpf('1234567890'))->toBeString('1234567890')
        ->and(Numero::cpf('invalid_cpf'))->toBeString('invalid_cpf');
});

test('Retorna CNPJ original se inválido', function () {
    expect(Numero::cnpj('1234567800019'))->toBeString('1234567800019')
        ->and(Numero::cnpj('invalid_cnpj'))->toBeString('invalid_cnpj');
});


