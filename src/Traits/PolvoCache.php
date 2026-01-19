<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
     * Determina se uma chave possui cache ativo.
     */
    public function isAtivo()
    {
        return Cache::has($this->getKey()) && $this->permiteCache();
    }

    /**
     * Determina se permite por query em cache.
     */
    public function permiteCache(): bool
    {
        return $this->getTtl()->isFuture();
    }

    /**
     * Recupera resposta da query do cache.
     */
    public function get()
    {
        return Cache::get($this->getKey());
    }

    /**
     * Desabita o cache para a query a ser executada.
     */
    public function semCache()
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
    public function lembrar(string $ttl, ?string $key = null)
    {
        $this->ttl = $ttl;
        $this->key = $key;

        return $this;
    }

    /**
     * Adiciona resposta query no cache.
     */
    protected function put($response): void
    {
        if (! $this->permiteCache()) {
            return;
        }

        Cache::put($this->getKey(), $response, $this->getTtl());
    }
}
