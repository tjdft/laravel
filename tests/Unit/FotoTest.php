<?php

use Illuminate\Support\Facades\Http;

test('A url de foto é montada corretamente', function () {
    Http::fake();

    $this->get('/tjdft/fotos/123')->streamedContent();

    Http::assertSent(fn($request) => $request->url() == config('tjdft.fotos.url') . '/123.jpg');
});
