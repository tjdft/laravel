<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    @php
        $title = match ($status) {
            503 => "Erro interno do sistema.",
            500 => "Erro interno do sistema.",
            404 => "Página não encontrada.",
            401 => "Você não está autenticado.",
            403 => "Permissão negada.",
            default => "Erro desconhecido."
        };
    @endphp

    <x-main>
        <x-slot:content>
            <div class="text-center lg:mt-40">
                <x-icon name="lucide.shell" class="w-20 h-20 text-error" />

                <div class="text-2xl font-bold mt-5">{{ $title }}</div>
                <div class="text-lg mt-3">{{ $detail }}</div>

                <div class="grid lg:flex gap-3 justify-center mt-16">
                    <x-button
                        :label="$isLivewire ? 'Fechar' : 'Recarregar'"
                        :icon="$isLivewire ? 'lucide.x' : 'lucide.rotate-cw'"
                        :onclick="$isLivewire ? 'window.parent.document.getElementById(\'livewire-error\').remove()' : 'window.location.reload()'"
                        @class(["btn-primary", "hidden" => in_array($status, [401, 403])])
                    />
                    <x-button label="Ir para o Início" icon="lucide.home" onclick="window.parent.location.href = '/'" class="btn-outline" />
                    <x-button label="Fazer login novamente" icon="lucide.shield-check" onclick="window.parent.location.href = '/'" class="btn-outline" />
                </div>
            </div>
        </x-slot:content>
    </x-main>
</body>
</html>
