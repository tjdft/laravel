<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Support\Carbon;

/**
 * Controle de cache do Polvo
 */
trait PolvoCache
{
    /** Tempo de cache padrão  */
    protected $ttl = '1 hour';

    /** Chave de cache usada */
    protected ?string $key = null;

    /**
     * Controle de cache do Polvo
     */
    public function cache(): self
    {
        return $this;
    }

    /**
     * Recupera o TTL do cache já convertido.
     */
    public function getTtl(): Carbon
    {
        return now()->parse($this->ttl);
    }

    /**
     * Retorna a chave de cache para a query.
     * Utiliza a chave personalizada definida anteriormente ou monta baseada no corpo da query.
     */
    public function getKey(): string
    {
        return $this->key ?? 'polvo-' . hash('sha256', strtolower(static::$query) . '-' . $this->ttl);
    }

    /**
     * Determina se o cache ainda é valido.
     */
    public function isValido(): bool
    {
        return cache()->has($this->getKey()) && $this->isExpirado();
    }

    /**
     * Determina se o tempo de cache expirou
     */
    public function isExpirado(): bool
    {
        return $this->getTtl()->isFuture();
    }

    /**
     * Recupera resposta da query do cache.
     */
    public function get(): mixed
    {
        return cache()->get($this->getKey());
    }

    /**
     * Desabita o cache para a query a ser executada.
     */
    public function semCache(): self
    {
        $this->ttl = 0;

        return $this;
    }

    /**
     * Personalizar o período de cache para a query a ser executada.
     *
     * @param  string  $ttl  Ex: '1 hour', '10 minutes' ...
     * @param  string  $key  chave de cache personalizada
     */
    public function lembrar(string $ttl, ?string $key = null): self
    {
        $this->ttl = $ttl;
        $this->key = $key;

        return $this;
    }

    /**
     * Adiciona resposta query no cache.
     */
    protected function put(mixed $response): void
    {
        if (! $this->isExpirado()) {
            return;
        }

        cache()->put($this->getKey(), $response, $this->getTtl());
    }
}
