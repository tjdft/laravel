<?php

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use TJDFT\Laravel\Mail\FeedbackMail;
use TJDFT\Laravel\Services\SmaxService;

beforeEach(function () {
    // Retorna um token de autenticação do SMAX
    Http::fake([
        '*/auth/authentication-endpoint/authenticate/*' => Http::response(['tokenXXXXXXXXXXXXXXXXXX']),
    ]);

    $this->user = $this->login();

    $this->feedback = [
        "conteudo" => "Site ficou ótimo",
        "url" => "http://localhost:8010/pagina-teste",
    ];
});

test('Registra requisicao no SMAX', function () {
    // Retorna um PersonID do SMAX
    fakePersonID();

    // Resposta de sucesso no SMAX
    Http::fake([
        "*/bulk" => Http::response([
            'meta' => [
                "completion_status" => 'OK'
            ]
        ])
    ]);

    // Quando eu enviar um feedback
    new SmaxService()->criarRequisicao(...$this->feedback);

    // Então nenhum email será enviado
    Mail::assertNotSent(FeedbackMail::class);
});

test('Envia email quando usuario nao tem cadastro no SMAX', function () {
    // Retorna vazio ao tentar obter o PersonID
    Http::fake([
        "*/Person*" => Http::response([
            'entities' => []
        ])
    ]);

    // Quando eu enviar um feedback
    new SmaxService()->criarRequisicao(...$this->feedback);

    // Então um e-mail é enviado com o feedback
    Mail::assertSent(FeedbackMail::class);
});

test('Envia email quando ha erro EXPLICITO durante o envio ao SMAX', function () {
    // Retorna um PersonID do SMAX
    fakePersonID();

    // Simula falha EXPLICITA ao criar (erro 500)
    Http::fake([
        "*/bulk*" => Http::response([], 500)
    ]);

    // Quando eu enviar um feedback
    new SmaxService()->criarRequisicao(...$this->feedback);

    // Então um e-mail é enviado com o feedback
    Mail::assertSent(FeedbackMail::class);
});

test('Envia email quando ha erro IMPLICITO durante o envio ao SMAX', function () {
    // Retorna um PersonID do SMAX
    fakePersonID();

    // Simula falha IMPLICITA ao criar
    // Não houve erro HTTP, mas descrito no corpo da resposta
    Http::fake([
        "*/bulk" => Http::response([
            'meta' => [
                "completion_status" => 'FAILED'
            ]
        ])
    ]);

    // Quando eu enviar um feedback
    new SmaxService()->criarRequisicao(...$this->feedback);

    // Então um e-mail é enviado com o feedback
    Mail::assertSent(FeedbackMail::class);
});

test('Corpo do email', function () {
    Mail::sendNow(new FeedbackMail($this->user, ...$this->feedback));

    // Então o email com os dados do formulário foi encaminhado com sucesso
    Mail::assertSent(FeedbackMail::class, function (Mailable $email) {
        return $email->hasSubject("Feedback - Teste") &&
            $email->hasFrom($this->user->email) &&
            $email->hasTo(str(config('tjdft.smax.fallback_emails'))->explode(',')->toArray()) &&
            $email->assertSeeInText($this->user->nome) &&
            $email->assertSeeInText($this->user->email) &&
            $email->assertSeeInText($this->feedback['conteudo']) &&
            $email->assertSeeInText($this->feedback['url']);
    });
});

function fakePersonID(): void
{
    Http::fake([
        "*/Person*" => Http::response([
            'entities' => [
                [
                    'properties' => [
                        'Id' => 999
                    ]
                ]
            ]
        ])
    ]);
}
