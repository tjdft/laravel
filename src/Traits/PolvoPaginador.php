<?php

namespace TJDFT\Laravel\Traits;

/**
 * Paginação automática
 */
trait PolvoPaginador
{
    /**
     * Pagina automaticamente o resultado e retorna lista completa
     *
     * @param  string  $method  Nome do método local a ser executado.
     * @param ?mixed  $args  Argumentos a serem repassados para execução do método.
     */
    protected function paginaTudo(string $method, ...$args)
    {
        $pagina = 1;
        $temMaisPaginas = true;
        $resposta = collect([]);

        while ($temMaisPaginas) {
            $args['pagina'] = $pagina++;
            $resultado = $this->$method(...$args);
            $resposta = $resposta->mergeRecursive($resultado);
            $resposta['paginatorInfo'] = $resultado['paginatorInfo'] ?? [];
            $temMaisPaginas = $resultado['paginatorInfo']['hasMorePages'] ?? false;
        }

        return $resposta;
    }
}
