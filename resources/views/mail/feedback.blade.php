<!-- @formatter:off -->
<x-mail::message>
<div align="center">
    @if(file_exists(base_path('public/imagens/logo.png')))
        <img src="{{ $message->embed(base_path('public/imagens/logo.png')) }}" alt="Logo" style="width: 64px; margin: 0 auto; display: block;">
    @endif
</div>

<br>

---

**Nome:** {{ $user->nome }}

**Email:** {{ $user->email }}

**URL:** {{ $url ?? '-' }}

**Data:** {{ now()->format('d/m/Y H:i') }}

---

# Mensagem

{{ $conteudo }}

</x-mail::message>
<!-- @formatter:on -->
